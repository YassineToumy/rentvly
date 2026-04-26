"""
Flask service for rental price prediction.
Loads the CatBoost model once at startup, serves predictions via HTTP.

Run: python main.py
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
from catboost import CatBoostRegressor, Pool
import pandas as pd
import numpy as np
import json
import os
from dotenv import load_dotenv

load_dotenv()

# ============================================================
# LOAD MODEL + METADATA AT STARTUP
# ============================================================

MODEL_PATH = os.getenv("MODEL_PATH", "model.cbm")
META_PATH = os.getenv("METADATA_PATH", os.getenv("META_PATH", "model_metadata.json"))

model = CatBoostRegressor()
metadata = {}
ALL_FEATURES = []
CAT_FEATURES = []
CAT_INDICES = []
MODEL_READY = False
MODEL_ERROR = ""

try:
    model.load_model(MODEL_PATH)
    with open(META_PATH, "r", encoding="utf-8") as f:
        metadata = json.load(f)

    ALL_FEATURES = model.feature_names_
    CAT_FEATURES = metadata.get("categorical_features", [])
    CAT_INDICES = [ALL_FEATURES.index(f) for f in CAT_FEATURES if f in ALL_FEATURES]
    MODEL_READY = True

    print(f"✅ Model loaded: {MODEL_PATH}")
    print(f"   Features: {len(ALL_FEATURES)}")
    print(f"   Categorical: {CAT_FEATURES} (indices: {CAT_INDICES})")
except Exception as e:
    MODEL_ERROR = str(e)
    print(f"❌ Model startup failed: {MODEL_ERROR}")
    print(f"   Expected model path: {MODEL_PATH}")
    print(f"   Expected metadata path: {META_PATH}")

# ============================================================
# FLASK APP
# ============================================================

app = Flask(__name__)
CORS(app)

# ============================================================
# SAFE TYPE HELPERS
# ============================================================

def safe_float(val, default=0.0):
    if val is None:
        return default
    try:
        return float(val)
    except (ValueError, TypeError):
        return default

def safe_int(val, default=0):
    if val is None:
        return default
    try:
        return int(float(val))
    except (ValueError, TypeError):
        return default

def safe_str(val, default="unknown"):
    if val is None or str(val).strip() == "" or str(val).lower() == "none":
        return default
    return str(val).strip()

# ============================================================
# FEATURE COMPUTATION
# ============================================================

def compute_features(data: dict) -> pd.DataFrame:
    """
    Transform input dict into the exact feature vector the model expects.
    """

    # --- Raw values ---
    surfaceArea = safe_float(data.get("surfaceArea"), 30)
    roomsQuantity = safe_int(data.get("roomsQuantity"), 1)
    bedroomsQuantity = safe_int(data.get("bedroomsQuantity"), 0)
    bathroomsQuantity = safe_int(data.get("bathroomsQuantity"), 0)
    showerRoomsQuantity = safe_int(data.get("showerRoomsQuantity"), 0)
    toiletQuantity = safe_int(data.get("toiletQuantity"), 0)
    floor_val = safe_int(data.get("floor"), 0)
    floorQuantity = safe_int(data.get("floorQuantity"), 0)
    yearOfConstruction = safe_int(data.get("yearOfConstruction"), 2000)
    charges = safe_float(data.get("charges"), 0)
    energyValue = safe_float(data.get("energyValue"), 0)
    greenhouseGazValue = safe_float(data.get("greenhouseGazValue"), 0)
    parkingPlacesQuantity = safe_int(data.get("parkingPlacesQuantity"), 0)
    garagesQuantity = safe_int(data.get("garagesQuantity"), 0)
    landSurfaceArea = safe_float(data.get("landSurfaceArea"), 0)

    # Booleans
    isFurnished = int(bool(data.get("isFurnished") or False))
    newProperty = int(bool(data.get("newProperty") or False))
    hasCellar = int(bool(data.get("hasCellar") or False))
    hasBalcony = int(bool(data.get("hasBalcony") or False))
    hasTerrace = int(bool(data.get("hasTerrace") or False))
    hasGarden = int(bool(data.get("hasGarden") or False))
    hasPool = int(bool(data.get("hasPool") or False))
    hasElevator = int(bool(data.get("hasElevator") or False))
    hasIntercom = int(bool(data.get("hasIntercom") or False))
    hasAirConditioning = int(bool(data.get("hasAirConditioning") or False))
    hasFireplace = int(bool(data.get("hasFireplace") or False))
    hasSeparateToilet = int(bool(data.get("hasSeparateToilet") or False))

    # Categorical
    city = safe_str(data.get("city"))
    postalCode = safe_str(data.get("postalCode"), "00000")
    department = safe_str(data.get("department"), "") or postalCode[:2]
    district_name = safe_str(data.get("district_name"))
    propertyType = safe_str(data.get("propertyType"), "flat")
    energyClassification = safe_str(data.get("energyClassification"), "D")
    heating_type_normalized = safe_str(data.get("heating_type_normalized"), "individual")

    # --- Derived features ---
    age_of_property = max(0, min(300, 2026 - yearOfConstruction)) if yearOfConstruction > 1800 else 0
    rooms_safe = max(roomsQuantity, 1)
    beds_safe = max(bedroomsQuantity, 1)
    fq_safe = max(floorQuantity, 1)

    room_surface_ratio = surfaceArea / rooms_safe
    surface_per_bedroom = surfaceArea / beds_safe
    is_studio = 1 if roomsQuantity == 1 else 0
    relative_floor = floor_val / fq_safe
    is_top_floor = 1 if floor_val == floorQuantity and floorQuantity > 0 else 0
    is_ground_floor = 1 if floor_val == 0 else 0

    equipment_score = sum([hasElevator, hasBalcony, hasTerrace, hasGarden,
                           hasPool, hasAirConditioning, isFurnished, hasCellar])
    outdoor_score = sum([hasBalcony, hasTerrace, hasGarden, hasPool])

    emap = {"A": 1, "B": 2, "C": 3, "D": 4, "E": 5, "F": 6, "G": 7}
    energy_class_numeric = emap.get(energyClassification, 4)
    energy_numeric = energy_class_numeric
    property_type_numeric = 0 if propertyType == "flat" else 1
    charges_ratio = charges / surfaceArea if surfaceArea > 0 else 0

    # Not available at inference time
    city_median_price = 0
    price_ratio_city = 0

    # --- Build row in model's exact feature order ---
    row = {
        "charges": charges,
        "surfaceArea": surfaceArea,
        "landSurfaceArea": landSurfaceArea,
        "roomsQuantity": roomsQuantity,
        "bedroomsQuantity": bedroomsQuantity,
        "bathroomsQuantity": bathroomsQuantity,
        "showerRoomsQuantity": showerRoomsQuantity,
        "toiletQuantity": toiletQuantity,
        "floor": floor_val,
        "floorQuantity": floorQuantity,
        "yearOfConstruction": yearOfConstruction,
        "newProperty": newProperty,
        "isFurnished": isFurnished,
        "hasCellar": hasCellar,
        "hasBalcony": hasBalcony,
        "hasTerrace": hasTerrace,
        "hasGarden": hasGarden,
        "hasPool": hasPool,
        "hasElevator": hasElevator,
        "hasIntercom": hasIntercom,
        "hasAirConditioning": hasAirConditioning,
        "hasFireplace": hasFireplace,
        "hasSeparateToilet": hasSeparateToilet,
        "energyValue": energyValue,
        "greenhouseGazValue": greenhouseGazValue,
        "parkingPlacesQuantity": parkingPlacesQuantity,
        "garagesQuantity": garagesQuantity,
        "age_of_property": age_of_property,
        "room_surface_ratio": room_surface_ratio,
        "equipment_score": equipment_score,
        "energy_class_numeric": energy_class_numeric,
        "charges_ratio": charges_ratio,
        "surface_per_bedroom": surface_per_bedroom,
        "is_studio": is_studio,
        "relative_floor": relative_floor,
        "is_top_floor": is_top_floor,
        "is_ground_floor": is_ground_floor,
        "outdoor_score": outdoor_score,
        "energy_numeric": energy_numeric,
        "property_type_numeric": property_type_numeric,
        "city_median_price": city_median_price,
        "price_ratio_city": price_ratio_city,
        "city": city,
        "postalCode": postalCode,
        "department": department,
        "district_name": district_name,
        "propertyType": propertyType,
        "energyClassification": energyClassification,
        "heating_type_normalized": heating_type_normalized,
    }

    return pd.DataFrame([row], columns=ALL_FEATURES)


def ensure_model_ready():
    if MODEL_READY:
        return None
    return jsonify({
        "success": False,
        "error": "Model is not loaded",
        "details": MODEL_ERROR,
        "expected_model_path": MODEL_PATH,
        "expected_metadata_path": META_PATH,
    }), 503


def predict_from_df(df: pd.DataFrame) -> float:
    pool = Pool(data=df, cat_features=CAT_INDICES)
    return float(model.predict(pool)[0])


# ============================================================
# ROUTES
# ============================================================

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
    """Predict monthly rental price."""
    try:
        not_ready = ensure_model_ready()
        if not_ready:
            return not_ready

        data = request.get_json()
        if not data:
            return jsonify({"error": "No JSON body provided"}), 400

        df = compute_features(data)
        prediction = predict_from_df(df)

        mape = metadata.get("metrics", {}).get("test", {}).get("mape", 13.0)
        margin = prediction * (mape / 100)

        return jsonify({
            "success": True,
            "data": {
                "predicted_rent": round(prediction, 2),
                "confidence_range": {
                    "low": round(prediction - margin, 2),
                    "high": round(prediction + margin, 2),
                    "mape_pct": round(mape, 1),
                },
                "model_metrics": metadata.get("metrics", {}).get("test", {}),
            }
        })

    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500


@app.route("/rentability", methods=["POST"])
def rentability():
    """Calculate rental profitability."""
    try:
        not_ready = ensure_model_ready()
        if not_ready:
            return not_ready

        data = request.get_json()
        if not data:
            return jsonify({"error": "No JSON body provided"}), 400

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


# ============================================================
# RUN
# ============================================================

if __name__ == "__main__":
    port = int(os.getenv("PORT", 8000))
    debug = os.getenv("FLASK_DEBUG", "false").lower() == "true"
    print(f"🚀 Starting Flask on port {port}")
    app.run(host="0.0.0.0", port=port, debug=debug)