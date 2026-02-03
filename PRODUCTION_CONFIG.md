# Production Environment Configuration

## Environment Variables
- `DATABASE_URL`: Database connection string
- `REDIS_URL`: Redis server connection string
- `JWT_SECRET`: Secret for signing JWTs
- `API_KEY`: External API key for service integration

## Security Hardening Scripts
1. **Secure SSH Configuration**
   - Edit `/etc/ssh/sshd_config`
   - Disable root login: `PermitRootLogin no`
   - Change the default SSH port: `Port 2222`

2. **Firewall Configuration**
   - Use `ufw` to allow only essential ports:
     - `sudo ufw allow 22`
     - `sudo ufw allow 80`
     - `sudo ufw allow 443`

3. **Regular Updates**
   - Schedule regular updates for the OS and installed applications using cron jobs.

## SSL/TLS Setup
1. **Install Certbot**
   - `sudo apt-get install certbot`

2. **Obtain SSL Certificate**
   - `sudo certbot --nginx -d yourdomain.com`

3. **Auto-Renewal**
   - Ensure auto-renewal is set up with:
   - `sudo certbot renew --dry-run`

## Load Balancing
- Use Nginx for load balancing with the following configuration:
```nginx
http {
    upstream app {
        server backend1.example.com;
        server backend2.example.com;
    }

    server {
        location / {
            proxy_pass http://app;
        }
    }
}
```

## Deployment Commands
- ### Deploy using Docker
  ```bash
  docker-compose up --build -d
  ```

- ### Rollback Commands
  ```bash
  docker-compose down
  docker-compose up --build -d
  ```

- ### Check Logs
  ```bash
  docker-compose logs -f
  ```

---