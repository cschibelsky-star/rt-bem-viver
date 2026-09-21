#!/bin/sh
set -eu

SEED_DIR="/opt/rtbem-seed-data"
DATA_DIR="/var/www/html/data"

mkdir -p "$DATA_DIR" /var/www/html/uploads/events /var/www/html/uploads/social /var/www/html/uploads/cities

if [ -d "$SEED_DIR" ]; then
  for src in "$SEED_DIR"/*.json; do
    [ -e "$src" ] || continue
    name="$(basename "$src")"
    if [ ! -f "$DATA_DIR/$name" ]; then
      cp "$src" "$DATA_DIR/$name"
    fi
  done
fi

php -r '
$path="/var/www/html/data/users.json";
if (is_file($path)) {
  $users=json_decode(file_get_contents($path), true);
  if (is_array($users)) {
    $changed=false;
    foreach ($users as &$u) {
      if (!array_key_exists("must_change_password",$u)) {
        $u["must_change_password"]=true;
        $changed=true;
      }
    }
    unset($u);
    if ($changed) {
      file_put_contents($path, json_encode($users, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
  }
}
'

chown -R www-data:www-data "$DATA_DIR" /var/www/html/uploads || true

exec docker-php-entrypoint apache2-foreground
