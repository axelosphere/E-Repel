import os
import pandas as pd
import psycopg2
from sklearn.ensemble import RandomForestRegressor
from datetime import datetime, timedelta
import json
import sys

try:
    # Get environment variables (these are set in Render)
    DB_HOST = os.getenv("DB_HOST")
    DB_PORT = os.getenv("DB_PORT", "5432")
    DB_NAME = os.getenv("DB_NAME")
    DB_USER = os.getenv("DB_USER")
    DB_PASS = os.getenv("DB_PASS")

    # Connect to PostgreSQL
    conn = psycopg2.connect(
        host=DB_HOST,
        port=DB_PORT,
        dbname=DB_NAME,
        user=DB_USER,
        password=DB_PASS
    )

    query = """
        SELECT 
            DATE_TRUNC('hour', detection_time) AS hour_slot,
            SUM(count) AS total_count
        FROM (
            SELECT egret_detection_time AS detection_time, egret_count AS count FROM egret_detections
            UNION ALL
            SELECT kingfisher_detection_time AS detection_time, kingfisher_count AS count FROM kingfisher_detections
        ) AS combined
        GROUP BY hour_slot
        ORDER BY hour_slot;
    """

    df = pd.read_sql(query, conn)
    conn.close()

    # Ensure we have data
    if df.empty:
        print(json.dumps({"labels": [], "data": []}))
        sys.exit(0)

    # Prepare features
    df['hour_slot'] = pd.to_datetime(df['hour_slot'])
    df['hour'] = df['hour_slot'].dt.hour
    df['dayofweek'] = df['hour_slot'].dt.dayofweek
    df['month'] = df['hour_slot'].dt.month

    X = df[['hour', 'dayofweek', 'month']]
    y = df['total_count']

    # Train model
    model = RandomForestRegressor(n_estimators=100, random_state=42)
    model.fit(X, y)

    # Predict next 24 hours
    future_times = [
        datetime.now().replace(minute=0, second=0, microsecond=0) + timedelta(hours=i)
        for i in range(1, 25)
    ]

    future_df = pd.DataFrame({
        'hour': [t.hour for t in future_times],
        'dayofweek': [t.weekday() for t in future_times],
        'month': [t.month for t in future_times]
    })

    future_df['predicted_count'] = model.predict(future_df[['hour', 'dayofweek', 'month']])

    # Output as JSON for PHP to read
    predictions = {
        "labels": [t.strftime("%H:00") for t in future_times],
        "data": future_df['predicted_count'].round(1).tolist()
    }

    print(json.dumps(predictions))

except Exception as e:
    # Output error as empty JSON to avoid breaking PHP
    print(json.dumps({"labels": [], "data": []}))
    sys.stderr.write(str(e))
