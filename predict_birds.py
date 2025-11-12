import pandas as pd
import pymysql
from sklearn.ensemble import RandomForestRegressor
from datetime import datetime, timedelta
import json

# Database connection
conn = pymysql.connect(host="localhost", user="root", password="", database="capstone_db")

query = """
SELECT DATE_FORMAT(detection_time, '%Y-%m-%d %H:00') AS hour_slot,
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

# Convert to datetime
df['hour_slot'] = pd.to_datetime(df['hour_slot'])
df['hour'] = df['hour_slot'].dt.hour
df['dayofweek'] = df['hour_slot'].dt.dayofweek
df['month'] = df['hour_slot'].dt.month

# Train model
X = df[['hour', 'dayofweek', 'month']]
y = df['total_count']

model = RandomForestRegressor(n_estimators=100, random_state=42)
model.fit(X, y)

# Predict next 24 hours
future_times = [datetime.now().replace(minute=0, second=0, microsecond=0) + timedelta(hours=i) for i in range(1, 25)]
future_df = pd.DataFrame({
    'hour': [t.hour for t in future_times],
    'dayofweek': [t.weekday() for t in future_times],
    'month': [t.month for t in future_times]
})
future_df['predicted_count'] = model.predict(future_df[['hour', 'dayofweek', 'month']])

# Output as JSON
predictions = {
    "labels": [t.strftime("%H:00") for t in future_times],
    "data": future_df['predicted_count'].round(1).tolist()
}
print(json.dumps(predictions))
