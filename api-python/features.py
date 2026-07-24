"""
Feature definitions and engineering — aligned with rentvly-dashboard/model.ipynb
(No leakage: no city_median_price, price_ratio_city, or price-derived features.)
"""

from __future__ import annotations

import pandas as pd

TARGET = "price"

NUM_FEATURES = [
    "surfaceArea",
    "roomsQuantity",
    "bedroomsQuantity",
    "bathroomsQuantity",
    "showerRoomsQuantity",
    "toiletQuantity",
    "floor",
    "floorQuantity",
    "yearOfConstruction",
    "charges",
    "energyValue",
    "greenhouseGazValue",
    "parkingPlacesQuantity",
    "garagesQuantity",
    "landSurfaceArea",
]

BOOL_FEATURES = [
    "isFurnished",
    "newProperty",
    "hasCellar",
    "hasBalcony",
    "hasTerrace",
    "hasGarden",
    "hasPool",
    "hasElevator",
    "hasIntercom",
    "hasAirConditioning",
    "hasFireplace",
    "hasSeparateToilet",
]

CAT_FEATURES = [
    "city",
    "postalCode",
    "department",
    "district_name",
    "propertyType",
    "energyClassification",
    "heating_type_normalized",
]

DERIVED_FEATURES = [
    "age_of_property",
    "room_surface_ratio",
    "surface_per_bedroom",
    "is_studio",
    "relative_floor",
    "is_top_floor",
    "is_ground_floor",
    "equipment_score",
    "outdoor_score",
    "energy_class_numeric",
    "property_type_numeric",
]

FEATURE_COLUMNS = NUM_FEATURES + BOOL_FEATURES + CAT_FEATURES + DERIVED_FEATURES
CATEGORICAL_FEATURES = CAT_FEATURES

ENERGY_MAP = {"A": 1, "B": 2, "C": 3, "D": 4, "E": 5, "F": 6, "G": 7, "unknown": 4}
CURRENT_YEAR = 2026


def safe_float(val, default=0.0) -> float:
    if val is None:
        return default
    try:
        return float(val)
    except (ValueError, TypeError):
        return default


def safe_int(val, default=0) -> int:
    if val is None:
        return default
    try:
        return int(float(val))
    except (ValueError, TypeError):
        return default


def safe_str(val, default="unknown") -> str:
    if val is None or str(val).strip() == "" or str(val).lower() == "none":
        return default
    return str(val).strip()


def compute_derived_features(df: pd.DataFrame) -> pd.DataFrame:
    """Same logic as model.ipynb — derived from raw columns only."""
    df = df.copy()

    for col in NUM_FEATURES:
        if col in df.columns:
            df[col] = df[col].fillna(df[col].median())

    if "yearOfConstruction" in df.columns:
        df["age_of_property"] = (CURRENT_YEAR - df["yearOfConstruction"]).clip(0, 300)
    else:
        df["age_of_property"] = 0

    if "roomsQuantity" in df.columns and "surfaceArea" in df.columns:
        df["room_surface_ratio"] = df["surfaceArea"] / df["roomsQuantity"].replace(0, 1)
    else:
        df["room_surface_ratio"] = 0

    if "bedroomsQuantity" in df.columns and "surfaceArea" in df.columns:
        df["surface_per_bedroom"] = df["surfaceArea"] / df["bedroomsQuantity"].replace(0, 1)
    else:
        df["surface_per_bedroom"] = 0

    if "roomsQuantity" in df.columns:
        df["is_studio"] = (df["roomsQuantity"] == 1).astype(int)
    else:
        df["is_studio"] = 0

    if "floor" in df.columns and "floorQuantity" in df.columns:
        df["relative_floor"] = df["floor"] / df["floorQuantity"].replace(0, 1)
        df["is_top_floor"] = (df["floor"] == df["floorQuantity"]).astype(int)
        df["is_ground_floor"] = (df["floor"] == 0).astype(int)
    else:
        df["relative_floor"] = 0
        df["is_top_floor"] = 0
        df["is_ground_floor"] = 0

    equip_cols = [
        "hasElevator", "hasBalcony", "hasTerrace", "hasGarden",
        "hasPool", "hasAirConditioning", "isFurnished", "hasCellar",
    ]
    df["equipment_score"] = sum(df[c] for c in equip_cols if c in df.columns)

    outdoor_cols = ["hasBalcony", "hasTerrace", "hasGarden", "hasPool"]
    df["outdoor_score"] = sum(df[c] for c in outdoor_cols if c in df.columns)

    if "energyClassification" in df.columns:
        df["energy_class_numeric"] = (
            df["energyClassification"].map(ENERGY_MAP).fillna(4).astype(int)
        )
    else:
        df["energy_class_numeric"] = 4

    if "propertyType" in df.columns:
        df["property_type_numeric"] = (
            df["propertyType"].map({"flat": 0, "house": 1}).fillna(0).astype(int)
        )
    else:
        df["property_type_numeric"] = 0

    return df


def prepare_features(df: pd.DataFrame) -> pd.DataFrame:
    df = df.copy()
    for col in FEATURE_COLUMNS:
        if col not in df.columns:
            df[col] = "unknown" if col in CAT_FEATURES else 0
    return df[FEATURE_COLUMNS].copy()


def build_feature_row(data: dict) -> dict:
    """Build one inference row from API/Laravel payload (camelCase keys)."""
    postal = safe_str(data.get("postalCode"), "00000")
    row = {
        "surfaceArea": safe_float(data.get("surfaceArea"), 30),
        "roomsQuantity": safe_int(data.get("roomsQuantity"), 1),
        "bedroomsQuantity": safe_int(data.get("bedroomsQuantity"), 0),
        "bathroomsQuantity": safe_int(data.get("bathroomsQuantity"), 0),
        "showerRoomsQuantity": safe_int(data.get("showerRoomsQuantity"), 0),
        "toiletQuantity": safe_int(data.get("toiletQuantity"), 0),
        "floor": safe_int(data.get("floor"), 0),
        "floorQuantity": safe_int(data.get("floorQuantity"), 0),
        "yearOfConstruction": safe_int(data.get("yearOfConstruction"), 2000),
        "charges": safe_float(data.get("charges"), 0),
        "energyValue": safe_float(data.get("energyValue"), 0),
        "greenhouseGazValue": safe_float(data.get("greenhouseGazValue"), 0),
        "parkingPlacesQuantity": safe_int(data.get("parkingPlacesQuantity"), 0),
        "garagesQuantity": safe_int(data.get("garagesQuantity"), 0),
        "landSurfaceArea": safe_float(data.get("landSurfaceArea"), 0),
        "isFurnished": int(bool(data.get("isFurnished"))),
        "newProperty": int(bool(data.get("newProperty"))),
        "hasCellar": int(bool(data.get("hasCellar"))),
        "hasBalcony": int(bool(data.get("hasBalcony"))),
        "hasTerrace": int(bool(data.get("hasTerrace"))),
        "hasGarden": int(bool(data.get("hasGarden"))),
        "hasPool": int(bool(data.get("hasPool"))),
        "hasElevator": int(bool(data.get("hasElevator"))),
        "hasIntercom": int(bool(data.get("hasIntercom"))),
        "hasAirConditioning": int(bool(data.get("hasAirConditioning"))),
        "hasFireplace": int(bool(data.get("hasFireplace"))),
        "hasSeparateToilet": int(bool(data.get("hasSeparateToilet"))),
        "city": safe_str(data.get("city")),
        "postalCode": postal,
        "department": safe_str(data.get("department"), "") or postal[:2],
        "district_name": safe_str(data.get("district_name")),
        "propertyType": safe_str(data.get("propertyType"), "flat"),
        "energyClassification": safe_str(data.get("energyClassification"), "D"),
        "heating_type_normalized": safe_str(data.get("heating_type_normalized"), "individual"),
    }

    fe = compute_derived_features(pd.DataFrame([row]))
    return prepare_features(fe).iloc[0].to_dict()


def rows_to_dataframe(rows: list[dict], feature_names: list[str] | None = None) -> pd.DataFrame:
    columns = feature_names or FEATURE_COLUMNS
    return pd.DataFrame(rows, columns=columns)
