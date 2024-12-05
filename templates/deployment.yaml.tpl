apiVersion: apps/v1
kind: Deployment
metadata:
  name: {{service_snake}}-deployment
spec:
  replicas: 3
  selector:
    matchLabels:
      app: {{service_snake}}
  template:
    metadata:
      labels:
        app: {{service_snake}}
    spec:
      containers:
      - name: {{service_snake}}
        image: your-docker-repo/{{service_snake}}:latest
        ports:
        - containerPort: 50051
        env:
        - name: APP_ENV
          value: production
