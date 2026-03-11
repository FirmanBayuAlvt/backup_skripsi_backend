import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestRegressor
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
import joblib
import os

# Load data
DATA_PATH = 'data_historis.csv'
if not os.path.exists(DATA_PATH):
    print(f"Error: File {DATA_PATH} tidak ditemukan!")
    exit(1)

df = pd.read_csv(DATA_PATH)

# Pisahkan fitur dan target
if 'daily_gain' not in df.columns:
    print("Error: Kolom 'daily_gain' tidak ditemukan!")
    exit(1)

X = df.drop('daily_gain', axis=1)
y = df['daily_gain']

# One-hot encoding untuk kolom kategorikal
categorical_cols = X.select_dtypes(include=['object']).columns.tolist()
print(f"Kolom kategorikal: {categorical_cols}")
X = pd.get_dummies(X, columns=categorical_cols)

# Simpan nama kolom untuk digunakan saat prediksi
feature_names = X.columns.tolist()
print(f"Jumlah fitur setelah encoding: {len(feature_names)}")

# Split data
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
print(f"Train size: {len(X_train)}, Test size: {len(X_test)}")

# Inisialisasi dan training model
model = RandomForestRegressor(
    n_estimators=200,
    max_depth=10,
    min_samples_split=5,
    min_samples_leaf=2,
    random_state=42,
    n_jobs=-1
)
model.fit(X_train, y_train)

# Evaluasi
y_pred = model.predict(X_test)
mae = mean_absolute_error(y_test, y_pred)
rmse = np.sqrt(mean_squared_error(y_test, y_pred))
r2 = r2_score(y_test, y_pred)

print(f"MAE : {mae:.4f}")
print(f"RMSE: {rmse:.4f}")
print(f"R²  : {r2:.4f}")

# Simpan model dan feature names
joblib.dump(model, 'model_sklearn.pkl')
joblib.dump(feature_names, 'feature_names_sklearn.pkl')
print("Model dan feature names disimpan.")