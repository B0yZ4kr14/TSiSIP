FROM certbot/certbot:latest

USER root
RUN apk add --no-cache curl openssl

COPY deploy-hook.sh /usr/local/bin/deploy-hook.sh
RUN chmod +x /usr/local/bin/deploy-hook.sh

# Setup cron for daily renewal check at 02:00 UTC
RUN echo "0 2 * * * /usr/local/bin/certbot renew --quiet --deploy-hook /usr/local/bin/deploy-hook.sh" | crontab -

ENTRYPOINT ["crond", "-f"]
