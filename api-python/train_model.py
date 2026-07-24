"""
Retrain CatBoost using the pipeline from rentvly-dashboard/model.ipynb
(source: bienici.locations_clean, no target leakage).

Run from api-python/:
  python train_model.py
"""

from __future__ import annotations

import json
import os
from datetime import datetime, timezone

import numpy as np
import pandas as pd
from catboost import CatBoostRegressor, Pool
from dotenv import load_dotenv
from pymongo import MongoClient
from sklearn.metrics import (
    mean_absolute_error,
    mean_absolute_percentage_error,
    mean_squared_error,
    r2_score,
)
from sklearn.model_selection import train_test_split

from features import (
    BOOL_FEATURES,
    CAT_FEATURES,
    CATEGORICAL_FEATURES,
    DERIVED_FEATURES,
    FEATURE_COLUMNS,
    NUM_FEATURES,
    TARGET,
    compute_derived_features,
    prepare_features,
)

load_dotenv()

MODEL_PATH = os.getenv("MODEL_PATH", "model.cbm")
META_PATH = os.getenv("METADATA_PATH", os.getenv("META_PATH", "model_metadata.json"))
MONGO_URI = os.getenv("MONGODB_URI", os.getenv("MONGO_URI", "mongodb://root:root@72.60.215.111:27019"))
MONGO_DB = os.getenv("MONGODB_DATABASE", os.getenv("MONGO_DATABASE", "bienici"))
MONGO_COLLECTION = os.getenv(
    "MONGO_LOCATIONS_COLLECTION",
    os.getenv("COLLECTION_NAME", "locations_clean"),
)
RANDOM_STATE = int(os.getenv("TRAIN_RANDOM_STATE", "42"))
TEST_SIZE = float(os.getenv("TRAIN_TEST_SIZE", "0.15"))
VAL_SIZE = float(os.getenv("TRAIN_VAL_SIZE", "0.15"))


def load_data() -> pd.DataFrame:
    client = MongoClient(MONGO_URI, serverSelectionTimeoutMS=15000)
    col = client[MONGO_DB][MONGO_COLLECTION]
    total = col.count_documents({})
    print(f"Mongo {MONGO_DB}.{MONGO_COLLECTION}: {total} documents")
    df = pd.DataFrame(list(col.find({}, {"_id": 0})))
    client.close()
    print(f"Loaded shape: {df.shape}")
    return df


def clean_raw(df: pd.DataFrame) -> pd.DataFrame:
    df = df.copy()
    cols_needed = [TARGET] + NUM_FEATURES + BOOL_FEATURES + CAT_FEATURES
    cols_available = [c for c in cols_needed if c in df.columns]
    df = df[cols_available].copy()

    for col in BOOL_FEATURES:
        if col in df.columns:
            df[col] = df[col].fillna(False).astype(int)

    for col in CAT_FEATURES:
        if col in df.columns:
            df[col] = df[col].fillna("unknown").astype(str)

    df = df.dropna(subset=[TARGET])
    p1 = df[TARGET].quantile(0.01)
    p99 = df[TARGET].quantile(0.99)
    n_before = len(df)
    df = df[(df[TARGET] >= p1) & (df[TARGET] <= p99)]
    print(f"Outliers removed: {n_before - len(df)} | rent range: {df[TARGET].min():.0f}-{df[TARGET].max():.0f}")
    return df


def train_model(X_train, y_train, X_val, y_val) -> CatBoostRegressor:
    cat_indices = [FEATURE_COLUMNS.index(c) for c in CAT_FEATURES]
    train_pool = Pool(X_train, label=y_train, cat_features=cat_indices)
    val_pool = Pool(X_val, label=y_val, cat_features=cat_indices)

    model = CatBoostRegressor(
        iterations=2000,
        learning_rate=0.05,
        depth=8,
        l2_leaf_reg=5,
        cat_features=cat_indices,
        loss_function="RMSE",
        eval_metric="MAE",
        early_stopping_rounds=100,
        task_type="CPU",
        thread_count=-1,
        verbose=200,
        random_state=RANDOM_STATE,
    )

    print(f"Training: {len(X_train)} | Val: {len(X_val)} | Features: {X_train.shape[1]}")
    model.fit(train_pool, eval_set=val_pool, plot=False)
    return model


def evaluate_split(model, X, y, label: str) -> dict:
    y_pred = model.predict(X)
    return {
        "label": label,
        "samples": int(len(y)),
        "mae": float(mean_absolute_error(y, y_pred)),
        "rmse": float(np.sqrt(mean_squared_error(y, y_pred))),
        "r2": float(r2_score(y, y_pred)),
        "mape": float(mean_absolute_percentage_error(y, y_pred) * 100),
    }


def train() -> None:
    df = clean_raw(load_data())

    y = df[TARGET]
    X_raw = df.drop(columns=[TARGET])

    X_trainval_raw, X_test_raw, y_trainval, y_test = train_test_split(
        X_raw, y, test_size=TEST_SIZE, random_state=RANDOM_STATE, shuffle=True
    )
    val_ratio = VAL_SIZE / (1 - TEST_SIZE)
    X_train_raw, X_val_raw, y_train, y_val = train_test_split(
        X_trainval_raw, y_trainval, test_size=val_ratio, random_state=RANDOM_STATE, shuffle=True
    )

    print(f"Split — train: {len(X_train_raw)} | val: {len(X_val_raw)} | test: {len(X_test_raw)}")

    X_train = prepare_features(compute_derived_features(X_train_raw))
    X_val = prepare_features(compute_derived_features(X_val_raw))
    X_test = prepare_features(compute_derived_features(X_test_raw))

    model = train_model(X_train, y_train, X_val, y_val)

    metrics = {
        "train": evaluate_split(model, X_train, y_train, "train"),
        "val": evaluate_split(model, X_val, y_val, "val"),
        "test": evaluate_split(model, X_test, y_test, "test"),
    }

    model.save_model(MODEL_PATH)
    metadata = {
        "features": FEATURE_COLUMNS,
        "numeric_features": NUM_FEATURES,
        "bool_features": BOOL_FEATURES,
        "categorical_features": CATEGORICAL_FEATURES,
        "derived_features": DERIVED_FEATURES,
        "target": TARGET,
        "model_type": "CatBoostRegressor",
        "source_notebook": "rentvly-dashboard/model.ipynb",
        "mongo_source": f"{MONGO_DB}.{MONGO_COLLECTION}",
        "trained_at": datetime.now(timezone.utc).isoformat(),
        "metrics": {
            k: {m: round(v, 4) if isinstance(v, float) else v for m, v in d.items() if m != "label"}
            for k, d in metrics.items()
        },
    }

    with open(META_PATH, "w", encoding="utf-8") as f:
        json.dump(metadata, f, indent=2)

    print(f"Saved {MODEL_PATH} and {META_PATH}")
    print(
        f"Test MAE: {metrics['test']['mae']:.0f} | "
        f"MAPE: {metrics['test']['mape']:.1f}% | "
        f"R2: {metrics['test']['r2']:.4f}"
    )


if __name__ == "__main__":
    train()
