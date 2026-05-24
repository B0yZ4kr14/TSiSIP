FROM certbot/certbot@sha256:0107d084c225631fc64a8313e19adb07275f7296fde338f7dfa93986c80b2e3e

USER root
RUN apk update && apk upgrade --no-cache && apk add --no-cache curl openssl ca-certificates dcron

COPY deploy-hook.sh /usr/local/bin/deploy-hook.sh
RUN chmod +x /usr/local/bin/deploy-hook.sh

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY healthcheck.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/healthcheck.sh

# Setup cron for daily renewal check at 02:00 UTC
RUN echo "0 2 * * * /usr/local/bin/certbot renew --quiet --deploy-hook /usr/local/bin/deploy-hook.sh" | crontab -

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
