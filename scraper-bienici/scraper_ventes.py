#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Scraper Bien'ici — VENTES (achat)
Adapté du scraper locations (bienici_scraper/scraper.py).

Stratégie: subdivision adaptative pour contourner la limite ~2500 résultats.
Écrit dans MongoDB: bienici.vente
"""

import json
import os
import random
import time
from datetime import datetime
from typing import Dict, List, Optional, Tuple

from dotenv import load_dotenv
from pymongo import ASCENDING, MongoClient
from pymongo.errors import DuplicateKeyError

load_dotenv()

MAX_RESULTS_WINDOW = 2400
MIN_PRICE_SLICE = 1000  # € — tranches d'achat plus larges que les loyers
MAX_SUBDIVISION_DEPTH = 12


class BieniciVentesScraper:
    def __init__(self):
        self.mongo_uri = os.getenv(
            "MONGODB_URI",
            "mongodb://127.0.0.1:27017",
        )
        self.db_name = os.getenv("MONGODB_DATABASE", "bienici")
        self.collection_name = os.getenv("MONGO_VENTES_COLLECTION", "vente")

        self.client = MongoClient(self.mongo_uri)
        self.db = self.client[self.db_name]
        self.collection = self.db[self.collection_name]

        self.api_url = os.getenv(
            "BIENICI_API_URL", "https://www.bienici.com/realEstateAds.json"
        )
        self.delay = int(os.getenv("DELAY_BETWEEN_REQUESTS", 2))
        self.max_pages = int(os.getenv("MAX_PAGES", 100))
        self.items_per_page = int(os.getenv("ITEMS_PER_PAGE", 100))
        self.property_types = [
            t.strip()
            for t in os.getenv("PROPERTY_TYPES", "house").split(",")
            if t.strip()
        ]

        # Fourchettes de prix d'achat (seront subdivisées si nécessaire)
        self.initial_price_ranges = [
            (0, 50_000),
            (50_000, 100_000),
            (100_000, 150_000),
            (150_000, 200_000),
            (200_000, 250_000),
            (250_000, 300_000),
            (300_000, 400_000),
            (400_000, 500_000),
            (500_000, 750_000),
            (750_000, 1_000_000),
            (1_000_000, 1_500_000),
            (1_500_000, 2_500_000),
            (2_500_000, 5_000_000),
            (5_000_000, 50_000_000),
        ]

        self.stats = {
            "total_scraped": 0,
            "inserted": 0,
            "updated": 0,
            "skipped": 0,
            "errors": 0,
            "api_calls": 0,
            "subdivisions": 0,
        }

        self.create_indexes()

    def create_indexes(self):
        print(f"Index MongoDB sur `{self.db_name}.{self.collection_name}`...")
        self.collection.create_index([("id", ASCENDING)], unique=True, name="id_unique")
        self.collection.create_index([("city", ASCENDING)])
        self.collection.create_index([("postalCode", ASCENDING)])
        self.collection.create_index([("propertyType", ASCENDING)])
        self.collection.create_index([("price", ASCENDING)])
        self.collection.create_index(
            [
                ("city", ASCENDING),
                ("propertyType", ASCENDING),
                ("price", ASCENDING),
            ]
        )
        print("  OK\n")

    def fetch(self, filters: Dict, retries: int = 3) -> Optional[Dict]:
        headers = {
            "User-Agent": (
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
            ),
            "Accept": "application/json",
            "Accept-Language": "fr-FR,fr;q=0.9",
            "Referer": "https://www.bienici.com/",
        }
        params = {"filters": json.dumps(filters)}

        for attempt in range(retries):
            try:
                self.stats["api_calls"] += 1
                resp = requests_get(self.api_url, params=params, headers=headers, timeout=30)
                resp.raise_for_status()
                return resp.json()
            except Exception as e:
                wait = (2**attempt) + random.uniform(0, 1)
                print(f"      API error ({attempt + 1}/{retries}): {e}")
                if attempt < retries - 1:
                    time.sleep(wait)
                else:
                    self.stats["errors"] += 1
                    return None

    def probe_total(
        self, filter_type: str, property_type: str, price_min: int, price_max: int
    ) -> int:
        filters = {
            "size": 1,
            "from": 0,
            "filterType": filter_type,
            "propertyType": [property_type],
            "sortBy": "publicationDate",
            "sortOrder": "desc",
            "onTheMarket": [True],
            "minPrice": price_min,
            "maxPrice": price_max,
        }
        resp = self.fetch(filters)
        return resp.get("total", 0) if resp else 0

    def build_slices(
        self,
        filter_type: str,
        property_type: str,
        price_min: int,
        price_max: int,
        depth: int = 0,
    ) -> List[Tuple[int, int]]:
        total = self.probe_total(filter_type, property_type, price_min, price_max)

        if total <= MAX_RESULTS_WINDOW:
            return [(price_min, price_max)] if total > 0 else []

        if depth >= MAX_SUBDIVISION_DEPTH or (price_max - price_min) <= MIN_PRICE_SLICE:
            print(
                f"      Tranche {price_min}-{price_max}€ trop dense "
                f"({total} annonces), limite atteinte"
            )
            return [(price_min, price_max)]

        self.stats["subdivisions"] += 1
        mid = (price_min + price_max) // 2
        print(
            f"      Subdivision: {price_min}-{price_max}€ ({total}) "
            f"-> [{price_min}-{mid}] + [{mid}-{price_max}]"
        )

        left = self.build_slices(filter_type, property_type, price_min, mid, depth + 1)
        right = self.build_slices(filter_type, property_type, mid, price_max, depth + 1)
        time.sleep(0.5)
        return left + right

    def scrape_slice(
        self, filter_type: str, property_type: str, price_min: int, price_max: int
    ):
        from_index = 0
        page_num = 1

        while page_num <= self.max_pages:
            filters = {
                "size": self.items_per_page,
                "from": from_index,
                "filterType": filter_type,
                "propertyType": [property_type],
                "page": page_num,
                "sortBy": "publicationDate",
                "sortOrder": "desc",
                "onTheMarket": [True],
                "minPrice": price_min,
                "maxPrice": price_max,
            }

            resp = self.fetch(filters)
            if not resp:
                break

            annonces = resp.get("realEstateAds", [])
            total = resp.get("total", 0)
            if not annonces:
                break

            result = self.save_annonces(annonces)
            self.stats["total_scraped"] += len(annonces)
            self.stats["inserted"] += result["inserted"]
            self.stats["updated"] += result["updated"]
            self.stats["skipped"] += result["skipped"]

            print(
                f"        p{page_num}: {len(annonces)} "
                f"(+{result['inserted']} ~{result['updated']}) "
                f"- {from_index + len(annonces)}/{total}"
            )

            from_index += len(annonces)
            if from_index >= total:
                break

            page_num += 1
            time.sleep(self.delay)

    def scrape_property_type(self, filter_type: str, property_type: str):
        print(f"\n  {property_type.upper()}")
        print(f"  {'-' * 50}")

        all_slices = []
        for price_min, price_max in self.initial_price_ranges:
            all_slices.extend(
                self.build_slices(filter_type, property_type, price_min, price_max)
            )

        print(
            f"\n  {len(all_slices)} tranches "
            f"(après {self.stats['subdivisions']} subdivisions)\n"
        )

        for i, (p_min, p_max) in enumerate(all_slices, 1):
            total_est = self.probe_total(filter_type, property_type, p_min, p_max)
            print(f"    [{i}/{len(all_slices)}] {p_min}-{p_max}€ (~{total_est})")

            if total_est == 0:
                continue

            self.scrape_slice(filter_type, property_type, p_min, p_max)

            if i % 10 == 0:
                total_db = self.collection.count_documents({})
                print(
                    f"\n    Progression: {i}/{len(all_slices)} | "
                    f"DB: {total_db} | API: {self.stats['api_calls']}\n"
                )

    def scrape_all(self):
        start_time = time.time()
        print("\n" + "=" * 60)
        print("SCRAPER BIEN'ICI — VENTES (filterType=buy)")
        print(f"Mongo: {self.db_name}.{self.collection_name}")
        print(f"Types: {', '.join(self.property_types)}")
        print("=" * 60)

        for ptype in self.property_types:
            self.scrape_property_type("buy", ptype)

        self.print_stats(time.time() - start_time)

    def prepare_annonce(self, data: Dict) -> Dict:
        prepared = {
            "id": data.get("id"),
            "reference": data.get("reference"),
            "source": "bienici",
            "title": data.get("title"),
            "description": data.get("description"),
            "city": data.get("city"),
            "postalCode": data.get("postalCode"),
            "district": data.get("district"),
            "country": data.get("country"),
            "latitude": data.get("latitude"),
            "longitude": data.get("longitude"),
            "price": data.get("price"),
            "pricePerSquareMeter": data.get("pricePerSquareMeter"),
            "priceHasDecreased": data.get("priceHasDecreased"),
            "reducedVat": data.get("reducedVat"),
            "propertyType": data.get("propertyType"),
            "surfaceArea": data.get("surfaceArea"),
            "landSurfaceArea": data.get("landSurfaceArea"),
            "roomsQuantity": data.get("roomsQuantity"),
            "bedroomsQuantity": data.get("bedroomsQuantity"),
            "bathroomsQuantity": data.get("bathroomsQuantity"),
            "showerRoomsQuantity": data.get("showerRoomsQuantity"),
            "toiletQuantity": data.get("toiletQuantity"),
            "floor": data.get("floor"),
            "floorQuantity": data.get("floorQuantity"),
            "newProperty": data.get("newProperty"),
            "yearOfConstruction": data.get("yearOfConstruction"),
            "condition": data.get("condition"),
            "publicationDate": data.get("publicationDate"),
            "modificationDate": data.get("modificationDate"),
            "deliveryDate": data.get("deliveryDate"),
            "adType": data.get("adType"),
            "transactionType": data.get("transactionType"),
            "adTypeFR": data.get("adTypeFR"),
            "accountType": data.get("accountType"),
            "adCreatedByPro": data.get("adCreatedByPro"),
            "hasBalcony": data.get("hasBalcony"),
            "hasTerrace": data.get("hasTerrace"),
            "hasGarden": data.get("hasGarden"),
            "hasPool": data.get("hasPool"),
            "hasCellar": data.get("hasCellar"),
            "hasGarage": data.get("hasGarage"),
            "hasParking": data.get("hasParking"),
            "hasSeparateToilet": data.get("hasSeparateToilet"),
            "hasIntercom": data.get("hasIntercom"),
            "hasElevator": data.get("hasElevator"),
            "hasFireplace": data.get("hasFireplace"),
            "hasAirConditioning": data.get("hasAirConditioning"),
            "hasDisabledAccess": data.get("hasDisabledAccess"),
            "energyClassification": data.get("energyClassification"),
            "energyValue": data.get("energyValue"),
            "greenhouseGazClassification": data.get("greenhouseGazClassification"),
            "greenhouseGazValue": data.get("greenhouseGazValue"),
            "heating": data.get("heating"),
            "heatingType": data.get("heatingType"),
            "exposition": data.get("exposition"),
            "parkingPlacesQuantity": data.get("parkingPlacesQuantity"),
            "garagesQuantity": data.get("garagesQuantity"),
            "photos": data.get("photos", []),
            "photosCount": data.get("photosCount"),
            "virtualTour": data.get("virtualTour"),
            "agency": data.get("agency"),
            "agencyId": data.get("agencyId"),
            "agencyName": data.get("agencyName"),
            "agencyFeePercentage": data.get("agencyFeePercentage"),
            "blurInfo": data.get("blurInfo"),
            "contact": data.get("contact"),
            "status": data.get("status"),
            "tags": data.get("tags", []),
            "isExclusive": data.get("isExclusive"),
            "isNew": data.get("isNew"),
            "filterType": "buy",
            "scraped_at": datetime.utcnow(),
            "updated_at": datetime.utcnow(),
        }
        return {k: v for k, v in prepared.items() if v is not None}

    def save_annonces(self, annonces: List[Dict]) -> Dict:
        if not annonces:
            return {"inserted": 0, "updated": 0, "skipped": 0}

        inserted = updated = skipped = 0

        for annonce in annonces:
            try:
                prepared = self.prepare_annonce(annonce)
                aid = prepared.get("id")
                if not aid:
                    skipped += 1
                    continue

                existing = self.collection.find_one({"id": aid})
                if existing:
                    self.collection.update_one({"id": aid}, {"$set": prepared})
                    updated += 1
                else:
                    prepared["created_at"] = datetime.utcnow()
                    self.collection.insert_one(prepared)
                    inserted += 1
            except DuplicateKeyError:
                skipped += 1
            except Exception as e:
                print(f"        Erreur save: {str(e)[:80]}")
                skipped += 1

        return {"inserted": inserted, "updated": updated, "skipped": skipped}

    def print_stats(self, duration: float):
        total_db = self.collection.count_documents({})
        print("\n" + "=" * 60)
        print("STATISTIQUES FINALES — VENTES")
        print("=" * 60)
        print(f"  Total scrapé:      {self.stats['total_scraped']}")
        print(f"  Nouvelles:         {self.stats['inserted']}")
        print(f"  Mises à jour:      {self.stats['updated']}")
        print(f"  Ignorées:          {self.stats['skipped']}")
        print(f"  Erreurs:           {self.stats['errors']}")
        print(f"  Appels API:        {self.stats['api_calls']}")
        print(f"  Subdivisions:      {self.stats['subdivisions']}")
        print(f"  Durée:             {duration:.0f}s ({duration / 60:.1f} min)")
        print(f"  Total en DB:       {total_db}")
        print(f"  Collection:        {self.db_name}.{self.collection_name}")
        print("=" * 60 + "\n")

    def close(self):
        self.client.close()


def requests_get(url, **kwargs):
    import requests

    return requests.get(url, **kwargs)


def main():
    scraper = BieniciVentesScraper()
    try:
        scraper.scrape_all()
    except KeyboardInterrupt:
        print("\n\nInterrompu")
        scraper.print_stats(0)
    except Exception as e:
        print(f"\n\nErreur: {e}")
        import traceback

        traceback.print_exc()
    finally:
        scraper.close()


if __name__ == "__main__":
    main()
