"""
Flask service for rental price prediction.
Loads the CatBoost model once at startup, serves predictions via HTTP.

Run: python main.py
Retrain: python train_model.py
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
from catboost import CatBoostRegressor, Pool
import pandas as pd
import json
import os
from dotenv import load_dotenv

from features import (
    CATEGORICAL_FEATURES,
    FEATURE_COLUMNS,
    build_feature_row,
    rows_to_dataframe,
)

load_dotenv()

MODEL_PATH = os.getenv("MODEL_PATH", "model.cbm")
META_PATH = os.getenv("METADATA_PATH", os.getenv("META_PATH", "model_metadata.json"))

model = CatBoostRegressor()
metadata = {}
ALL_FEATURES = FEATURE_COLUMNS
CAT_FEATURES = CATEGORICAL_FEATURES
CAT_INDICES = [ALL_FEATURES.index(f) for f in CAT_FEATURES if f in ALL_FEATURES]
MODEL_READY = False
MODEL_ERROR = ""

try:
    model.load_model(MODEL_PATH)
    with open(META_PATH, "r", encoding="utf-8") as f:
        metadata = json.load(f)

    ALL_FEATURES = list(model.feature_names_)
    CAT_FEATURES = metadata.get("categorical_features", CATEGORICAL_FEATURES)
    CAT_INDICES = [ALL_FEATURES.index(f) for f in CAT_FEATURES if f in ALL_FEATURES]
    MODEL_READY = True

    print(f"Model loaded: {MODEL_PATH}")
    print(f"   Features: {len(ALL_FEATURES)}")
except Exception as e:
    MODEL_ERROR = str(e)
    print(f"Model startup failed: {MODEL_ERROR}")
    print(f"   Run: python train_model.py")
    print(f"   Expected: {MODEL_PATH} and {META_PATH}")


app = Flask(__name__)
CORS(app)


def compute_features(data: dict) -> pd.DataFrame:
    row = build_feature_row(data)
    columns = ALL_FEATURES if MODEL_READY else FEATURE_COLUMNS
    return rows_to_dataframe([row], feature_names=columns)


def ensure_model_ready():
    if MODEL_READY:
        return None
    return jsonify({
        "success": False,
        "error": "Model is not loaded",
        "details": MODEL_ERROR,
        "hint": "Run: cd api-python && python train_model.py",
        "expected_model_path": MODEL_PATH,
        "expected_metadata_path": META_PATH,
    }), 503


def predict_from_df(df: pd.DataFrame) -> float:
    pool = Pool(data=df, cat_features=CAT_INDICES)
    return float(model.predict(pool)[0])


@app.route("/health", methods=["GET"])
def health():
    return jsonify({
        "status": "ok" if MODEL_READY else "degraded",
        "model_ready": MODEL_READY,
        "model_error": MODEL_ERROR if not MODEL_READY else None,
        "model": MODEL_PATH,
        "metadata": META_PATH,
        "features": len(ALL_FEATURES),
    })


@app.route("/predict", methods=["POST"])
def predict():
    try:
        not_ready = ensure_model_ready()
        if not_ready:
            return not_ready

        data = request.get_json()
        if not data:
            return jsonify({"error": "No JSON body provided"}), 400

        df = compute_features(data)
        prediction = predict_from_df(df)

        mape_pct = metadata.get("metrics", {}).get("test", {}).get("mape", 12.0)
        margin = prediction * (mape_pct / 100)

        return jsonify({
            "success": True,
            "data": {
                "predicted_rent": round(prediction, 2),
                "confidence_range": {
                    "low": round(prediction - margin, 2),
                    "high": round(prediction + margin, 2),
                    "mape_pct": round(mape_pct, 1),
                },
                "model_metrics": metadata.get("metrics", {}).get("test", {}),
            }
        })

    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500


@app.route("/rentability", methods=["POST"])
def rentability():
    try:
        not_ready = ensure_model_ready()
        if not_ready:
            return not_ready

        data = request.get_json()
        if not data:
            return jsonify({"error": "No JSON body provided"}), 400

        from features import safe_float

        purchase_price = safe_float(data.get("purchase_price"), 0)
        property_data = data.get("property", {})

        if purchase_price <= 0:
            return jsonify({"error": "purchase_price must be > 0"}), 400

        df = compute_features(property_data)
        predicted_rent = predict_from_df(df)

        monthly_charges = safe_float(property_data.get("charges"), 0)
        annual_rent = predicted_rent * 12

        gross_yield = (annual_rent / purchase_price) * 100
        annual_charges = monthly_charges * 12
        estimated_costs = annual_rent * 0.30
        net_annual = annual_rent - annual_charges - estimated_costs
        net_yield = (net_annual / purchase_price) * 100
        monthly_cashflow = net_annual / 12
        payback = purchase_price / net_annual if net_annual > 0 else 999

        return jsonify({
            "success": True,
            "data": {
                "predicted_rent": round(predicted_rent, 2),
                "purchase_price": round(purchase_price, 2),
                "annual_rent": round(annual_rent, 2),
                "gross_yield": round(gross_yield, 2),
                "net_yield": round(net_yield, 2),
                "monthly_charges": round(monthly_charges, 2),
                "monthly_cashflow": round(monthly_cashflow, 2),
                "payback_years": round(payback, 1),
            }
        })

    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500


if __name__ == "__main__":
    port = int(os.getenv("PORT", 8000))
    debug = os.getenv("FLASK_DEBUG", "false").lower() == "true"
    print(f"Starting Flask on port {port}")
    app.run(host="0.0.0.0", port=port, debug=debug)
