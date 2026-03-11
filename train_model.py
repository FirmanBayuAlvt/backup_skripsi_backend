"""
Script untuk melatih model Gradient Boosted Trees menggunakan TensorFlow Decision Forests.
Input: data_historis.csv
Output: model tersimpan di folder 'model_gbt' dan file 'model_info.pkl'
"""

import pandas as pd
import numpy as np
import tensorflow as tf
import tensorflow_decision_forests as tfdf
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
import joblib
import logging
import os

# Konfigurasi logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

# ============================================
# 1. Load dan Persiapan Data
# ============================================
DATA_PATH = 'data_historis.csv'
if not os.path.exists(DATA_PATH):
    logger.error(f"File {DATA_PATH} tidak ditemukan!")
    exit(1)

logger.info("Memuat data...")
df = pd.read_csv(DATA_PATH)

# Pisahkan fitur dan target
if 'daily_gain' not in df.columns:
    logger.error("Kolom 'daily_gain' tidak ditemukan dalam data!")
    exit(1)

X = df.drop('daily_gain', axis=1)
y = df['daily_gain']

# Identifikasi kolom kategorikal (asumsi: tipe object atau string)
categorical_cols = X.select_dtypes(include=['object']).columns.tolist()
logger.info(f"Kolom kategorikal: {categorical_cols}")

# Konversi kolom kategorikal ke string (untuk TF-DF)
for col in categorical_cols:
    X[col] = X[col].astype(str)

# Split data (80% train, 20% test)
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, random_state=42
)
logger.info(f"Jumlah data train: {len(X_train)}, test: {len(X_test)}")

# ============================================
# 2. Konversi ke TensorFlow Dataset
# ============================================
def pandas_to_tf_dataset(X, y, task=tfdf.keras.Task.REGRESSION):
    """Mengubah DataFrame pandas menjadi TensorFlow Dataset untuk TF-DF."""
    dataset = tfdf.keras.pd_dataframe_to_tf_dataset(
        X,
        label=y,
        task=task
    )
    return dataset

train_ds = pandas_to_tf_dataset(X_train, y_train)
test_ds = pandas_to_tf_dataset(X_test, y_test)

# ============================================
# 3. Inisialisasi Model
# ============================================
logger.info("Membangun model Gradient Boosted Trees...")
model = tfdf.keras.GradientBoostedTreesModel(
    verbose=2,
    num_trees=300,
    max_depth=6,
    learning_rate=0.1,
    min_examples=10,
    categorical_algorithm="RANDOM",
    task=tfdf.keras.Task.REGRESSION,
    random_seed=42,
)

# ============================================
# 4. Training
# ============================================
logger.info("Memulai training...")
model.fit(train_ds)

# ============================================
# 5. Evaluasi
# ============================================
logger.info("Evaluasi model pada data test...")
evaluation = model.evaluate(test_ds, return_dict=True)
logger.info("Hasil evaluasi (metric dari TF-DF):")
for metric, value in evaluation.items():
    logger.info(f"  {metric}: {value:.4f}")

# Prediksi untuk metrik tambahan
y_pred = model.predict(test_ds).flatten()
mae = mean_absolute_error(y_test, y_pred)
rmse = np.sqrt(mean_squared_error(y_test, y_pred))
r2 = r2_score(y_test, y_pred)

logger.info(f"MAE  : {mae:.4f}")
logger.info(f"RMSE : {rmse:.4f}")
logger.info(f"R²   : {r2:.4f}")

# ============================================
# 6. Simpan Model dan Informasi
# ============================================
MODEL_DIR = 'model_gbt'
model.save(MODEL_DIR)
logger.info(f"Model disimpan di folder '{MODEL_DIR}'")

# Simpan daftar fitur dan informasi preprocessing
model_info = {
    'feature_names': X.columns.tolist(),
    'categorical_cols': categorical_cols,
    'metrics': {
        'mae': mae,
        'rmse': rmse,
        'r2': r2
    }
}
joblib.dump(model_info, 'model_info.pkl')
logger.info("Informasi model disimpan di 'model_info.pkl'")