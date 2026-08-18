#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Cleaner ventes — lit `bienici.vente`, écrit `bienici.ventes_clean`
au format attendu par `php artisan import:ventes` (snake_case).
"""

import os
from datetime import datetime, timezone

from dotenv import load_dotenv
from pymongo import ASCENDING, MongoClient
from pymongo.errors import BulkWriteError

load_dotenv()

SOURCE_COLLECTION = os.getenv("MONGO_VENTES_COLLECTION", "vente")
CLEAN_COLLECTION = os.getenv("MONGO_VENTES_CLEAN_COLLECTION", "ventes_clean")
BATCH_SIZE = 500

# Seuils achat (€ et m²)
MIN_PRICE = 30_000
MAX_PRICE = 20_000_000
MIN_SURFACE = 9
MAX_SURFACE = 1_000
MAX_ROOMS = 30
MIN_PRICE_PER_M2 = 500
MAX_PRICE_PER_M2 = 40_000


def safe_get(doc, key, default=None):
    val = doc.get(key)
    return val if val is not None else default


def to_number(value):
    """Bien'ici parfois renvoie price/surface/rooms en liste ou dict."""
    if value is None or value is False:
        return None
    if isinstance(value, bool):
        return None
    if isinstance(value, (int, float)):
        return float(value)
    if isinstance(value, (list, tuple)):
        for item in value:
            parsed = to_number(item)
            if parsed is not None:
                return parsed
        return None
    if isinstance(value, dict):
        for key in ("value", "min", "max", "amount", "price"):
            if key in value:
                parsed = to_number(value.get(key))
                if parsed is not None:
                    return parsed
        return None
    if isinstance(value, str):
        cleaned = (
            value.strip()
            .replace("\u00a0", "")
            .replace(" ", "")
            .replace("€", "")
            .replace(",", ".")
        )
        if not cleaned:
            return None
        try:
            return float(cleaned)
        except ValueError:
            return None
    return None


def extract_district(doc):
    district = doc.get("district")
    if not isinstance(district, dict):
        return {}
    return {
        "district_name": district.get("name") or district.get("libelle"),
        "code_insee": district.get("code_insee") or district.get("insee_code"),
    }


def validate(doc):
    price = to_number(doc.get("price"))
    if price is None or price < MIN_PRICE or price > MAX_PRICE:
        return False, "prix_invalide"

    surface = to_number(doc.get("surfaceArea"))
    if surface is None or surface < MIN_SURFACE or surface > MAX_SURFACE:
        return False, "surface_invalide"

    if doc.get("propertyType") not in ("flat", "house"):
        return False, "type_invalide"

    if not doc.get("city") or not doc.get("postalCode"):
        return False, "localisation_manquante"

    rooms = to_number(doc.get("roomsQuantity"))
    if rooms is not None and rooms > MAX_ROOMS:
        return False, "pieces_aberrant"

    ppm2 = price / surface
    if ppm2 < MIN_PRICE_PER_M2 or ppm2 > MAX_PRICE_PER_M2:
        return False, "prix_m2_aberrant"

    return True, None


def equipment_score(doc):
    return sum(
        [
            bool(safe_get(doc, "hasElevator", False)),
            bool(safe_get(doc, "hasParking", False))
            or (to_number(safe_get(doc, "parkingPlacesQuantity")) or 0) > 0
            or bool(safe_get(doc, "hasGarage", False)),
            bool(safe_get(doc, "hasBalcony", False))
            or bool(safe_get(doc, "hasTerrace", False)),
            bool(safe_get(doc, "hasGarden", False)),
            bool(safe_get(doc, "hasPool", False)),
            bool(safe_get(doc, "hasAirConditioning", False)),
            bool(safe_get(doc, "hasCellar", False)),
            bool(safe_get(doc, "hasFireplace", False)),
        ]
    )


def owner_info(doc):
    account = safe_get(doc, "accountType")
    agency_name = safe_get(doc, "agencyName")
    if isinstance(doc.get("agency"), dict):
        agency_name = agency_name or doc["agency"].get("name")
        account = account or doc["agency"].get("accountType")

    is_pro = bool(safe_get(doc, "adCreatedByPro", False)) or (
        str(account or "").lower() in ("pro", "agency", "agent")
    )
    return {
        "owner_type": account,
        "owner_name": agency_name,
        "is_pro": is_pro,
    }


def clean_document(doc):
    district = extract_district(doc)
    postal = str(doc["postalCode"])
    surface = to_number(doc.get("surfaceArea"))
    rooms = to_number(doc.get("roomsQuantity"))
    price = to_number(doc.get("price"))
    ppm2 = to_number(doc.get("pricePerSquareMeter"))
    if ppm2 is None and surface:
        ppm2 = round(price / surface, 2)

    surface_per_room = None
    if rooms and rooms > 0 and surface:
        surface_per_room = round(surface / rooms, 2)

    owner = owner_info(doc)
    photos = safe_get(doc, "photos", []) or []

    blur = safe_get(doc, "blurInfo")
    code_insee = district.get("code_insee")
    if not code_insee and isinstance(blur, dict):
        code_insee = blur.get("codeInsee") or blur.get("code_insee")

    return {
        "id": doc["id"],
        "title": safe_get(doc, "title", ""),
        "description": safe_get(doc, "description"),
        "property_type": doc["propertyType"],
        "is_new_property": bool(safe_get(doc, "newProperty", False)),
        "price": int(price),
        "price_per_sqm": round(ppm2, 2) if ppm2 is not None else None,
        "price_has_decreased": bool(safe_get(doc, "priceHasDecreased", False)),
        "reduced_vat": bool(safe_get(doc, "reducedVat", False)),
        "surface_area": surface,
        "rooms_quantity": int(rooms) if rooms is not None else None,
        "surface_per_room": surface_per_room,
        "is_disabled_friendly": bool(safe_get(doc, "hasDisabledAccess", False)),
        "has_elevator": bool(safe_get(doc, "hasElevator", False)),
        "has_garden": bool(safe_get(doc, "hasGarden", False)),
        "has_terrace": bool(safe_get(doc, "hasTerrace", False)),
        "has_balcony": bool(safe_get(doc, "hasBalcony", False)),
        "has_pool": bool(safe_get(doc, "hasPool", False)),
        "has_parking": bool(safe_get(doc, "hasParking", False))
        or bool(safe_get(doc, "hasGarage", False)),
        "has_cellar": bool(safe_get(doc, "hasCellar", False)),
        "has_air_conditioning": bool(safe_get(doc, "hasAirConditioning", False)),
        "has_fireplace": bool(safe_get(doc, "hasFireplace", False)),
        "equipment_score": equipment_score(doc),
        "city": doc["city"],
        "postal_code": postal,
        "department_code": postal[:2] if len(postal) >= 2 else postal,
        "district_name": district.get("district_name"),
        "code_insee": code_insee,
        "latitude": safe_get(doc, "latitude"),
        "longitude": safe_get(doc, "longitude"),
        "owner_type": owner["owner_type"],
        "owner_name": owner["owner_name"],
        "is_pro": owner["is_pro"],
        "photos": photos,
        "photos_count": int(safe_get(doc, "photosCount") or len(photos)),
        "publication_date": safe_get(doc, "publicationDate"),
        "modification_date": safe_get(doc, "modificationDate"),
        "delivery_date": safe_get(doc, "deliveryDate"),
        "scraped_at": safe_get(doc, "scraped_at"),
        "cleaned_at": datetime.now(timezone.utc),
    }


def connect_db():
    uri = os.getenv(
        "MONGODB_URI",
        "mongodb://127.0.0.1:27017",
    )
    name = os.getenv("MONGODB_DATABASE", "bienici")
    client = MongoClient(uri)
    return client, client[name]


def setup_clean_collection(db):
    clean = db[CLEAN_COLLECTION]
    clean.drop()
    print(f"Collection `{CLEAN_COLLECTION}` réinitialisée")
    clean.create_index([("id", ASCENDING)], unique=True, name="id_unique")
    clean.create_index([("city", ASCENDING)])
    clean.create_index([("postal_code", ASCENDING)])
    clean.create_index([("property_type", ASCENDING)])
    clean.create_index([("price", ASCENDING)])
    print("Index créés\n")
    return clean


def insert_batch(collection, batch):
    inserted = duplicates = 0
    try:
        result = collection.insert_many(batch, ordered=False)
        inserted = len(result.inserted_ids)
    except BulkWriteError as e:
        inserted = e.details.get("nInserted", 0)
        duplicates = len(batch) - inserted
    return inserted, duplicates


def run_pipeline(source, clean):
    total = source.count_documents({})
    print(f"Documents dans `{SOURCE_COLLECTION}`: {total}\n")
    if total == 0:
        print("Rien à traiter. Lancez d'abord: python scraper_ventes.py")
        return

    stats = {
        "inserted": 0,
        "duplicates": 0,
        "prix_invalide": 0,
        "surface_invalide": 0,
        "type_invalide": 0,
        "localisation_manquante": 0,
        "pieces_aberrant": 0,
        "prix_m2_aberrant": 0,
    }

    batch = []
    cursor = source.find({}, {"_id": 0}, batch_size=BATCH_SIZE)

    for doc in cursor:
        ok, reason = validate(doc)
        if not ok:
            stats[reason] = stats.get(reason, 0) + 1
            continue

        batch.append(clean_document(doc))
        if len(batch) >= BATCH_SIZE:
            ins, dup = insert_batch(clean, batch)
            stats["inserted"] += ins
            stats["duplicates"] += dup
            batch = []
            processed = sum(stats.values())
            print(
                f"   {processed}/{total} traités (valides: {stats['inserted']})",
                end="\r",
                flush=True,
            )

    if batch:
        ins, dup = insert_batch(clean, batch)
        stats["inserted"] += ins
        stats["duplicates"] += dup

    rejected = total - stats["inserted"] - stats["duplicates"]
    print(f"\n\n{'=' * 60}")
    print("RÉSULTATS NETTOYAGE VENTES")
    print(f"{'=' * 60}")
    print(f"  Total:     {total}")
    print(f"  Valides:   {stats['inserted']}")
    print(f"  Rejetés:   {rejected}")
    for key in (
        "prix_invalide",
        "surface_invalide",
        "type_invalide",
        "localisation_manquante",
        "pieces_aberrant",
        "prix_m2_aberrant",
    ):
        if stats.get(key):
            print(f"    - {key}: {stats[key]}")
    print(f"  Collection: {CLEAN_COLLECTION}")
    print(f"{'=' * 60}\n")


def main():
    print("\n" + "=" * 60)
    print("NETTOYAGE — Ventes Bien'ici")
    print("=" * 60 + "\n")

    client, db = connect_db()
    source = db[SOURCE_COLLECTION]
    clean = setup_clean_collection(db)
    run_pipeline(source, clean)
    client.close()
    print("Ensuite: cd ../backend && php artisan import:ventes\n")


if __name__ == "__main__":
    main()
