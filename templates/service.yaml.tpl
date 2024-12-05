apiVersion: v1
kind: Service
metadata:
  name: {{service_snake}}-service
spec:
  selector:
    app: {{service_snake}}
  ports:
    - protocol: TCP
      port: 50051
      targetPort: 50051
  type: ClusterIP
