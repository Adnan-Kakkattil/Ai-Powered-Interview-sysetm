FROM python:3.9-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir --upgrade pip && \
    pip install --no-cache-dir -r requirements.txt

COPY . .

RUN mkdir -p uploads/resumes /data && chmod -R 755 uploads

EXPOSE 5000

HEALTHCHECK --interval=30s --timeout=10s --start-period=10s --retries=3 \
    CMD python -c "import socket; s=socket.socket(); s.settimeout(1); result=s.connect_ex(('localhost', 5000)); s.close(); exit(0 if result == 0 else 1)" || exit 1

CMD ["sh", "-c", "python init_db.py 2>/dev/null || true; python app.py"]
