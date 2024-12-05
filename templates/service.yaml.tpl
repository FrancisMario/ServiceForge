apiVersion: v1
kind: Service
metadata:
  name: {{SERVICE_NAME}}-service
  labels:
    app: {{SERVICE_NAME}}
spec:
  type: ClusterIP
  selector:
    app: {{SERVICE_NAME}}
  ports:
    - protocol: TCP
      port: 50051
      targetPort: 50051
