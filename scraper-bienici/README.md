# Scraper Bien'ici — Ventes (Rentvly)

Scrape les annonces **à vendre** depuis l’API Bien'ici et les stocke dans MongoDB local.

## Pipeline

```
python scraper_ventes.py   →  bienici.vente          (brut)
python cleaner_ventes.py   →  bienici.ventes_clean   (snake_case)
php artisan import:ventes  →  PostgreSQL ventes
```

## Setup

```powershell
cd scraper-bienici
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
copy .env.example .env
```

Assurez-vous que Mongo Docker tourne :

```powershell
cd ..
docker compose up -d mongo
```

## Lancer

```powershell
cd scraper-bienici
python scraper_ventes.py    # long (subdivision par prix)
python cleaner_ventes.py
cd ..\backend
php artisan import:ventes
```

## Config (`.env`)

| Variable | Défaut | Rôle |
|---|---|---|
| `MONGODB_URI` | `mongodb://root:root@127.0.0.1:27017/?authSource=admin` | Connexion Mongo |
| `MONGODB_DATABASE` | `bienici` | Base |
| `MONGO_VENTES_COLLECTION` | `vente` | Collection brute |
| `MONGO_VENTES_CLEAN_COLLECTION` | `ventes_clean` | Collection nettoyée |
| `PROPERTY_TYPES` | `house` | `house` (maisons), `flat` (appartements), ou `house,flat` |

Par défaut le scraper ne prend que les **maisons** (les appartements ont déjà été scrapés). Les nouvelles annonces s’ajoutent à la même collection `vente`.

Différence avec le scraper locations : `filterType=buy` + fourchettes de **prix d’achat**.
