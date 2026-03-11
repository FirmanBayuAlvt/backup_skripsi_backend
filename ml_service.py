import os
import logging
from flask import Flask, request, jsonify
from flask_cors import CORS
import pandas as pd
import numpy as np
import joblib

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

app = Flask(__name__)
CORS(app)

MODEL_PATH = 'model_sklearn.pkl'
FEATURES_PATH = 'feature_names_sklearn.pkl'

model = None
feature_names = []

try:
    model = joblib.load(MODEL_PATH)
    logger.info(f"Model loaded from {MODEL_PATH}")
except Exception as e:
    logger.error(f"Failed to load model: {e}")

try:
    feature_names = joblib.load(FEATURES_PATH)
    logger.info(f"Feature names loaded. Total features: {len(feature_names)}")
except Exception as e:
    logger.warning(f"Could not load feature names: {e}")

def prepare_input(data):
    """Mengubah input JSON menjadi DataFrame dengan one-hot encoding."""
    df = pd.DataFrame([data])
    
    # One-hot encoding untuk kolom kategorikal (sesuai dengan training)
    categorical_cols = ['breed_type', 'health_status']  # sesuaikan jika ada kolom lain
    df = pd.get_dummies(df, columns=categorical_cols)
    
    # Pastikan semua kolom yang dibutuhkan model ada (isi 0 jika tidak ada)
    if feature_names:
        for col in feature_names:
            if col not in df.columns:
                df[col] = 0
        df = df[feature_names]
    
    return df

@app.route('/health', methods=['GET'])
def health():
    if model is None:
        return jsonify({'status': 'unavailable', 'model_loaded': False}), 503
    return jsonify({
        'status': 'ok',
        'model_loaded': True,
        'features': feature_names
    })

@app.route('/predict', methods=['POST'])
def predict():
    if model is None:
        return jsonify({'error': 'Model tidak tersedia'}), 503

    data = request.get_json()
    if not data:
        return jsonify({'error': 'Tidak ada data input'}), 400

    logger.info(f"Request data: {data}")

    try:
        input_df = prepare_input(data)
        logger.debug(f"DataFrame input shape: {input_df.shape}")

        pred = model.predict(input_df)
        predicted_gain = float(pred[0])

        # Interval sederhana (bisa diganti dengan prediction interval jika perlu)
        lower = predicted_gain * 0.9
        upper = predicted_gain * 1.1
        confidence = 0.85  # nilai tetap, bisa diganti dengan metrik lain

        response = {
            'predicted_gain': predicted_gain,
            'confidence': confidence,
            'interval': {
                'lower': lower,
                'upper': upper
            }
        }
        logger.info(f"Prediksi: {predicted_gain:.4f} kg/hari")
        return jsonify(response)

    except Exception as e:
        logger.error(f"Error saat prediksi: {e}", exc_info=True)
        return jsonify({'error': 'Terjadi kesalahan internal'}), 500

if __name__ == '__main__':
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=False)