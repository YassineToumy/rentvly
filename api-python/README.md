# Rentvly prediction API (Flask + CatBoost)

Training pipeline is ported from **`rentvly-dashboard/model.ipynb`** (preferred over `model-catboost.ipynb`, which used price-derived features that cause leakage).

## Retrain

```powershell
cd api-python
pip install -r requirements.txt
python train_model.py
python main.py
```

Data source: Mongo **`bienici.locations_clean`**, target column **`price`** (monthly rent).

Check http://127.0.0.1:8000/health → `"model_ready": true`.

Expected test metrics (approx.): MAPE ~12%, MAE ~100–110€.

## Local stack

Backend `.env`: `PREDICTION_API_URL=http://127.0.0.1:8000`  
Frontend `.env`: `NUXT_PUBLIC_API_BASE=http://backend.test/api/v1`
