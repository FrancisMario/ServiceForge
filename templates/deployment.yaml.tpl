apiVersion: apps/v1
kind: Deployment
metadata:
  name: {{SERVICE_NAME}}-deployment
  labels:
    app: {{SERVICE_NAME}}
spec:
  replicas: 1
  selector:
    matchLabels:
      app: {{SERVICE_NAME}}
  template:
    metadata:
      labels:
        app: {{SERVICE_NAME}}
    spec:
      containers:
        - name: {{SERVICE_NAME}}-container
          image: your-docker-repo/{{SERVICE_NAME}}:latest
          ports:
            - containerPort: 50051
          env:
            - name: APP_ENV
              value: production
            - name: APP_DEBUG
              value: "false"
