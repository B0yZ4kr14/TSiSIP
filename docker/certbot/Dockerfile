FROM certbot/certbot:latest

USER root
RUN apk add --no-cache curl openssl ca-certificates dcron

COPY deploy-hook.sh /usr/local/bin/deploy-hook.sh
RUN chmod +x /usr/local/bin/deploy-hook.sh

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Setup cron for daily renewal check at 02:00 UTC
RUN echo "0 2 * * * /usr/local/bin/certbot renew --quiet --deploy-hook /usr/local/bin/deploy-hook.sh" | crontab -

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
