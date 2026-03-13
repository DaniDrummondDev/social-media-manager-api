FROM python:3.12-slim AS base

ARG UID=1000
ARG GID=1000

RUN groupadd -g ${GID} appuser && \
    useradd -u ${UID} -g appuser -m -s /bin/bash appuser

RUN apt-get update && apt-get install -y --no-install-recommends curl && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

RUN chown -R appuser:appuser /app

USER appuser

EXPOSE 8000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000", "--workers", "4"]
