FROM python:3.11-alpine
WORKDIR /app
COPY mock_trunk_server.py /app/
EXPOSE 5060/udp
CMD ["python3", "/app/mock_trunk_server.py", "--host", "0.0.0.0", "--port", "5060"]
