<?php
/**
 * End-to-end test of the OAuth 1.0a flow and the Tomboy sync API
 * against a running Nextcloud with grauphel enabled.
 *
 * The client side uses the PECL oauth extension, an implementation
 * independent from grauphel's own. The server does not need it.
 *
 * Needs two Nextcloud users:
 *   php -d extension=oauth.so tests/integration/oauth-sync.php \
 *       http://localhost:8080/index.php/apps/grauphel \
 *       admin:password bob:password
 */
if ($argc != 4) {
    fwrite(STDERR, "Usage: oauth-sync.php BASE-URL USER1:PASSWORD USER2:PASSWORD\n");
    exit(2);
}
if (!class_exists('OAuth')) {
    fwrite(STDERR, "PECL oauth extension is required to run this test\n");
    exit(2);
}

$base  = rtrim($argv[1], '/');
$users = array(explode(':', $argv[2], 2), explode(':', $argv[3], 2));
$fails = 0;

function check($name, $ok, $extra = '')
{
    global $fails;
    echo ($ok ? 'PASS ' : 'FAIL ') . $name
        . ($extra !== '' ? '  [' . $extra . ']' : '') . "\n";
    if (!$ok) {
        $fails++;
    }
}

function http($method, $url, $user = null, $opts = array())
{
    static $jars = array();
    $key = $user === null ? '' : $user[0];
    if (!isset($jars[$key])) {
        $jars[$key] = tempnam(sys_get_temp_dir(), 'jar');
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_COOKIEJAR      => $jars[$key],
        CURLOPT_COOKIEFILE     => $jars[$key],
    ));
    if ($user !== null) {
        curl_setopt($ch, CURLOPT_USERPWD, implode(':', $user));
    }
    if (isset($opts['headers'])) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['headers']);
    }
    if (isset($opts['body'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['body']);
    }
    $res = curl_exec($ch);
    $hs  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    return array(
        'status'  => curl_getinfo($ch, CURLINFO_HTTP_CODE),
        'headers' => substr($res, 0, $hs),
        'body'    => substr($res, $hs),
    );
}

function csrfToken($html)
{
    if (preg_match('/data-requesttoken="([^"]+)"/', $html, $m)) {
        return html_entity_decode($m[1]);
    }
    return '';
}

function client()
{
    return new OAuth(
        'anyone', 'anyone',
        OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_AUTHORIZATION
    );
}

/**
 * Signed GET like Tomboy's WebSync add-in does it: the query parameters
 * are signed and sent in the query string and the Authorization header
 *
 * @return array Response as returned by http()
 */
function tomboyGet($url, $query, $acc)
{
    $params = $query + array(
        'oauth_consumer_key'     => 'anyone',
        'oauth_nonce'            => (string) mt_rand(123400, 9999999),
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_timestamp'        => (string) time(),
        'oauth_token'            => $acc['oauth_token'],
        'oauth_version'          => '1.0',
    );
    ksort($params);
    $base = 'GET&' . rawurlencode($url) . '&'
        . rawurlencode(http_build_query($params, '', '&', PHP_QUERY_RFC3986));
    $params['oauth_signature'] = base64_encode(
        hash_hmac('sha1', $base, 'anyone&' . $acc['oauth_token_secret'], true)
    );
    $header = array();
    foreach ($params as $name => $value) {
        $header[] = $name . '="' . urlencode($value) . '"';
    }
    return http(
        'GET', $url . '?' . http_build_query($query), null,
        array('headers' => array(
            'Authorization: OAuth realm="' . $url . '",' . implode(',', $header)
        ))
    );
}

/**
 * Run the complete OAuth dance for a user
 *
 * @return array Access token and secret
 */
function authorize($api, $user, $verbose)
{
    global $base;
    $o = client();
    $req = $o->getRequestToken(
        $api['oauth_request_token_url'], 'http://localhost:8000/tomboy-web-sync/'
    );
    if ($verbose) {
        check(
            'request token',
            isset($req['oauth_token'], $req['oauth_token_secret'])
            && $req['oauth_callback_confirmed'] == 'true'
        );
    }

    $r = http(
        'GET',
        $api['oauth_authorize_url'] . '?oauth_token=' . $req['oauth_token']
        . '&client=Test+PC',
        $user
    );
    $csrf = csrfToken($r['body']);
    if ($verbose) {
        check('authorize page', $r['status'] == 200 && strpos($r['body'], 'Test PC') !== false, $r['status']);
        check('authorize page has CSRF token', $csrf !== '');

        //Tomdroid: the redirect to its app scheme must pass form-action
        $req2 = client()->getRequestToken(
            $api['oauth_request_token_url'], 'tomdroid://sync'
        );
        $r2 = http(
            'GET',
            $api['oauth_authorize_url'] . '?oauth_token=' . $req2['oauth_token'],
            $user
        );
        preg_match('/^Content-Security-Policy:.*form-action([^;\r\n]*)/mi', $r2['headers'], $m);
        $formAction = isset($m[1]) ? trim($m[1]) : '';
        check(
            'authorize page allows app scheme callback',
            strpos(" $formAction ", ' tomdroid: ') !== false,
            $formAction
        );
    }

    $form = array(
        'oauth_token' => $req['oauth_token'], 'client' => 'Test PC', 'auth' => 'ok',
    );
    if ($verbose) {
        $r = http('POST', "$base/oauth/confirm", $user, array('body' => http_build_query($form)));
        check('confirm without CSRF token is rejected', $r['status'] >= 400, $r['status']);
    }
    $form['requesttoken'] = $csrf;
    $r = http('POST', "$base/oauth/confirm", $user, array('body' => http_build_query($form)));
    preg_match('/^Location:\s*(\S+)/mi', $r['headers'], $m);
    $loc = isset($m[1]) ? $m[1] : '';
    parse_str((string) parse_url($loc, PHP_URL_QUERY), $cb);
    if ($verbose) {
        check(
            'confirm redirects to callback with verifier',
            $r['status'] == 303
            && strpos($loc, 'http://localhost:8000/tomboy-web-sync/') === 0
            && !empty($cb['oauth_verifier']),
            $r['status'] . ' ' . $loc
        );
    }

    $o->setToken($req['oauth_token'], $req['oauth_token_secret']);
    if ($verbose) {
        try {
            $o->getAccessToken($api['oauth_access_token_url'], '', 'vwrongverifier');
            check('wrong verifier is rejected', false);
        } catch (OAuthException $e) {
            check('wrong verifier is rejected', strpos($e->lastResponse, 'verifier_invalid') !== false, trim($e->lastResponse));
        }
    }
    $acc = $o->getAccessToken($api['oauth_access_token_url'], '', $cb['oauth_verifier']);
    if ($verbose) {
        check('access token', isset($acc['oauth_token'], $acc['oauth_token_secret']));
        try {
            $o->getAccessToken($api['oauth_access_token_url'], '', $cb['oauth_verifier']);
            check('verifier cannot be reused', false);
        } catch (OAuthException $e) {
            check('verifier cannot be reused', true, trim($e->lastResponse));
        }
    }
    return $acc;
}

try {
    // --- discovery, unauthenticated
    $r = http('GET', "$base/api/1.0");
    $api = json_decode($r['body'], true);
    check(
        'GET /api/1.0 unauthenticated',
        $r['status'] == 200 && isset($api['oauth_request_token_url']) && !isset($api['user-ref']),
        $r['status']
    );

    $acc = authorize($api, $users[0], true);
    $acc2 = authorize($api, $users[1], false);

    // --- signed API calls
    $o = client();
    $o->setToken($acc['oauth_token'], $acc['oauth_token_secret']);
    $o->fetch("$base/api/1.0/");
    $root = json_decode($o->getLastResponse(), true);
    check(
        'GET /api/1.0/ signed has user-ref',
        isset($root['user-ref']['api-ref'])
        && strpos($root['user-ref']['api-ref'], '/api/1.0/' . $users[0][0]) !== false
    );

    $o->fetch($root['user-ref']['api-ref']);
    $user = json_decode($o->getLastResponse(), true);
    $rev = $user['latest-sync-revision'];
    check('GET user', $user['user-name'] == $users[0][0] && is_int($rev), json_encode($rev));

    $guid = sprintf('%08x-bbbb-cccc-dddd-eeeeeeeeeeee', mt_rand());
    $put = json_encode(array(
        'latest-sync-revision' => $rev + 1,
        'note-changes' => array(array(
            'guid' => $guid,
            'title' => 'Synced &lt;note&gt;',
            'note-content' => "Synced &lt;note&gt;\nhello from the integration test",
            'note-content-version' => '0.3',
            'tags' => array('system:notebook:Work'),
        )),
    ));
    $o->fetch(
        $user['notes-ref']['api-ref'], $put, OAUTH_HTTP_METHOD_PUT,
        array('Content-Type' => 'application/json')
    );
    $saved = json_decode($o->getLastResponse(), true);
    check('PUT notes', $saved['latest-sync-revision'] == $rev + 1, substr($o->getLastResponse(), 0, 100));

    $o->fetch($user['notes-ref']['api-ref'], array('include_notes' => 'true'));
    $notes = json_decode($o->getLastResponse(), true);
    check(
        'GET notes?include_notes=true',
        in_array($guid, array_column($notes['notes'], 'guid'))
        && isset($notes['notes'][0]['note-content'])
    );

    $r = tomboyGet($user['notes-ref']['api-ref'], array('include_notes' => 'true'), $acc);
    $notes = json_decode($r['body'], true);
    check(
        'GET notes, Tomboy style with query parameters in the header',
        $r['status'] == 200 && in_array($guid, array_column((array) $notes['notes'], 'guid')),
        (string) $r['status']
    );

    $o->fetch($user['notes-ref']['api-ref'], array('since' => (string) $rev));
    $since = json_decode($o->getLastResponse(), true);
    check(
        'GET notes?since=REV returns only the new note',
        count($since['notes']) == 1 && $since['notes'][0]['guid'] == $guid
    );

    $o->fetch("$base/api/1.0/{$users[0][0]}/note/$guid");
    $n = json_decode($o->getLastResponse(), true);
    check('GET single note', $n['title'] == 'Synced &lt;note&gt;');

    // --- negative API tests
    $bad = client();
    $bad->setToken($acc['oauth_token'], 'swrongsecret');
    try {
        $bad->fetch($root['user-ref']['api-ref']);
        check('wrong token secret is rejected', false);
    } catch (OAuthException $e) {
        check(
            'wrong token secret is rejected',
            $e->getCode() == 401 && strpos($e->lastResponse, 'signature_invalid') !== false,
            $e->getCode() . ' ' . trim($e->lastResponse)
        );
    }

    $other = client();
    $other->setToken($acc2['oauth_token'], $acc2['oauth_token_secret']);
    try {
        $other->fetch("$base/api/1.0/{$users[0][0]}/notes");
        check("other user's token cannot read the notes", false);
    } catch (OAuthException $e) {
        check("other user's token cannot read the notes", $e->getCode() == 403, (string) $e->getCode());
    }

    $pt = new OAuth('anyone', 'anyone', OAUTH_SIG_METHOD_PLAINTEXT, OAUTH_AUTH_TYPE_AUTHORIZATION);
    $pt->setToken($acc['oauth_token'], $acc['oauth_token_secret']);
    $pt->fetch($root['user-ref']['api-ref']);
    check('PLAINTEXT signature accepted', json_decode($pt->getLastResponse(), true)['user-name'] == $users[0][0]);

    $q = new OAuth('anyone', 'anyone', OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
    $q->setToken($acc['oauth_token'], $acc['oauth_token_secret']);
    $q->fetch($root['user-ref']['api-ref']);
    check('OAuth params in query string accepted', json_decode($q->getLastResponse(), true)['user-name'] == $users[0][0]);

    // --- web interface
    foreach (array('', '/tokens', '/database', '/tag/grauphel:special:all',
        "/note/$guid", "/note/$guid.html", "/note/$guid.txt", "/note/$guid.xml") as $path
    ) {
        $r = http('GET', $base . $path, $users[0]);
        check('GET ' . ($path ?: '/'), $r['status'] == 200, (string) $r['status']);
    }
    $r = http('GET', "$base/note/$guid", $users[0]);
    check('note title is escaped', strpos($r['body'], 'Synced &lt;note&gt;') !== false && strpos($r['body'], 'Synced <note>') === false);

    // --- token management
    $r = http('GET', "$base/tokens", $users[0]);
    check('tokens page lists the token', strpos($r['body'], $acc['oauth_token']) !== false);
    $csrf = csrfToken($r['body']);

    $r = http('DELETE', "$base/tokens/{$users[1][0]}/{$acc2['oauth_token']}", $users[0], array('headers' => array('requesttoken: ' . $csrf)));
    check("cannot delete other user's token", $r['status'] == 403, (string) $r['status']);

    $r = http('DELETE', "$base/tokens/{$users[0][0]}/{$acc['oauth_token']}", $users[0]);
    check('DELETE without CSRF token is rejected', $r['status'] == 412, (string) $r['status']);

    $r = http('DELETE', "$base/tokens/{$users[0][0]}/{$acc['oauth_token']}", $users[0], array('headers' => array('requesttoken: ' . $csrf)));
    check('DELETE own token', $r['status'] == 204, (string) $r['status']);
    try {
        $o->fetch("$base/api/1.0/");
        check('deleted token no longer works', false);
    } catch (OAuthException $e) {
        check('deleted token no longer works', $e->getCode() == 401, $e->getCode() . ' ' . trim($e->lastResponse));
    }
} catch (OAuthException $e) {
    check('unexpected OAuth error', false, $e->getMessage() . ' ' . $e->lastResponse);
}

echo $fails == 0 ? "\nALL PASSED\n" : "\n$fails FAILED\n";
exit($fails ? 1 : 0);
