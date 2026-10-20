#!/bin/sh
# Run only in a disposable container: network none, no production volumes.
# Auth/data helpers below are test doubles; this does not certify production login.
set -eu
mkdir -p /tmp/app/admin /tmp/app/includes /tmp/app/uploads
cp /audit/candidate.php /tmp/app/admin/minha_senha.php
php -l /tmp/app/admin/minha_senha.php
cat > /tmp/app/includes/auth.php <<'PHP'
<?php
function current_user(){return ['username'=>'isolated-test','city_slug'=>'sumare'];}
function is_general_admin(){return false;}
function verify_csrf(){if(($_POST['csrf_token']??'')!=='isolated-test'){http_response_code(403);exit;}}
function read_json($name,$default=[]){return $name==='attractions'?[['id'=>'existing','city_slug'=>'sumare','image'=>'uploads/original.png','created_at'=>'test']]:$default;}
function write_json($name,$value){file_put_contents('/tmp/result.json',json_encode($value));}
function city_by_slug($slug){return $slug==='sumare'?['slug'=>'sumare']:null;}
function flash($message,$type){file_put_contents('/tmp/flash.json',json_encode([$type,$message]));}

PHP
cat > /tmp/fixtures.php <<'PHP'
<?php
$i=imagecreatetruecolor(1600,900); imagepng($i,'/tmp/input.png'); imagejpeg($i,'/tmp/input.jpg'); imagewebp($i,'/tmp/input.webp'); file_put_contents('/tmp/invalid.jpg','not-an-image'); file_put_contents('/tmp/large.png',file_get_contents('/tmp/input.png').str_repeat('x',6*1024*1024));
PHP
cat > /tmp/check.php <<'PHP'
<?php
$mode=$argv[1]; $file='/tmp/result.json';
if($mode==='reject'){
 if(is_file($file)||!is_file('/tmp/flash.json')||json_decode(file_get_contents('/tmp/flash.json'),true)[0]!=='error') {fwrite(STDERR,"FAIL reject\n");exit(1);}
}else{
 if(!is_file($file)){fwrite(STDERR,"FAIL no record: ".(@file_get_contents('/tmp/flash.json')?:'')."\n");exit(1);}
 $r=json_decode(file_get_contents($file),true)[0]; $path='/tmp/app/'.$r['image'];
 $info=@getimagesize($path);
 if(!$info||$info['mime']!==$mode||$info[0]!==1200||$info[1]!==675){fwrite(STDERR,"FAIL format/dimensions\n");exit(1);}
}
echo "PASS ".$argv[2]."\n";
PHP
php /tmp/fixtures.php
echo TESTING_candidate
run_case() {
  label="$1"; disabled="$2"; input="$3"; expected="$4"
  rm -f /tmp/result.json /tmp/flash.json
  php -d upload_max_filesize=10M -d disable_functions="$disabled" -S 127.0.0.1:18001 -t /tmp/app >/tmp/server.log 2>&1 &
  server_pid=$!
  for attempt in 1 2 3 4 5; do curl -s -o /dev/null http://127.0.0.1:18001/ && break || sleep 0.1; done
  status=$(curl -sS -o /tmp/response -w '%{http_code}' -F csrf_token=isolated-test -F action=save_attraction -F id=existing -F name=Teste -F description=Teste -F "image=@/tmp/$input" 'http://127.0.0.1:18001/admin/minha_senha.php?tab=atrativos')
  kill "$server_pid"; wait "$server_pid" || true
  test "$status" = 302
  php /tmp/check.php "$expected" "$label"
  if grep -E 'Fatal error|Uncaught' /tmp/server.log /tmp/response; then exit 1; fi
}
run_case jpeg '' input.jpg image/webp
run_case png '' input.png image/webp
run_case webp '' input.webp image/webp
run_case jpeg_no_webp imagewebp input.jpg image/png
run_case png_no_webp imagewebp input.png image/png
run_case webp_no_encoder imagewebp input.webp image/png
run_case no_webp_decoder imagecreatefromwebp input.webp reject
run_case no_gd imagecreatetruecolor input.png reject
run_case invalid '' invalid.jpg reject
run_case oversized '' large.png reject
echo ALL_TESTS_PASSED