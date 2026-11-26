<?php

namespace App\Docs;

class ApiIntroduction
{
    /**
     * Markdown shown on the Scramble documentation home page.
     */
    public static function markdown(): string
    {
        return <<<'MD'
        Every `/api` request must be signed.

        ## Headers

        | Header | Required | Value |
        | --- | --- | --- |
        | `X-Client-ID` | yes | Client id from the admin panel. Format: `client_` plus a number from 10000 to 99999. |
        | `X-Nonce` | yes | A random string, unique on each request to the same endpoint. |
        | `X-Api-Signature` | yes | Lowercase hex SHA-256 of the string described below. |

        Create the client under **Admin → API clients**. The secret key stays visible there. Outside production it starts with `sk_dev_`. In production it starts with `sk_prod_`.

        <div style="margin:0 0 1.25rem;padding:0.95rem 1rem;border:2px solid #d97706;border-radius:8px;background:#fff4cc;color:#7a3e00;line-height:1.5;"><b>Store the secret key securely and never send it with a request.</b> Only the client id is exposed, in the X-Client-ID header. The secret key stays on client's secure storage and is used only to build X-Api-Signature.</div>


        ## How to build the signature

        1. Collect the request payload: JSON fields, form text fields, and query parameters. Headers are not part of the payload. Uploaded files are excluded.
        2. Drop every field whose value is `null`. An empty string is kept. A field that was not sent is not part of the payload.
        3. Sort the remaining fields by key, A to Z, using binary order.
        4. Concatenate only the values, in that order, with no separator.
        5. Append the `X-Nonce` value, then the secret.
        6. Hash that string with SHA-256 and send the lowercase hex digest in `X-Api-Signature`.

        Each accepted signature is stored for one hour. Sending that same signature again is rejected for one hour.

        ```http
        HTTP/1.1 401 Unauthorized
        Content-Type: application/json

        {
          "success": false,
          "message": "The API signature has already been used.",
          "data": null,
          "errors": {
            "api_signature": [
              "The API signature has already been used."
            ]
          }
        }
        ```

        Format each value like this:

        - strings and numbers are written as plain text (`10`, `1.5`, `beta`)
        - booleans are `true` or `false`
        - objects are JSON with nulls removed, keys sorted, and slashes left unescaped
        - lists are JSON arrays with null items removed

        Sign each text value exactly as the client sends it. The signature is checked before the server normalizes anything. A phone number sent as `0812-3456-7890` is signed as `0812-3456-7890`, then stored as `6281234567890`.

        ### Uploaded files

        Leave every file out of the signature. On `POST /api/auth/register`, sign `name`, `phone`, `birth_date`, `gender`, and `referral_code` when that field is present. Do not include `profile_photo`.

        ### No payload

        `sha256(nonce + secret)`

        Nonce `k3p9Qx` and secret `sk_dev_secret`:

        ```text
        k3p9Qxsk_dev_secret
        ```

        ```text
        96c25359bf61c9c5745403c6da4bb1c2838f7d1d40dbadab865f274023378927
        ```

        ### With a payload

        `sha256(sorted values + nonce + secret)`

        ```json
        {
          "zebra": "zebra",
          "alpha": null,
          "active": true,
          "beta": "beta"
        }
        ```

        `alpha` is null, so it is skipped. The remaining keys in order are `active`, `beta`, `zebra`. Their values are `true`, `beta`, and `zebra`.

        ```text
        truebetazebrak3p9Qxsk_dev_secret
        ```

        ```text
        59810e402452613dc99e819a93553f12c510d05068df55719d3c72342c0632fb
        ```

        ## Example request

        ```http
        GET /api/server-time HTTP/1.1
        Accept: application/json
        X-Client-ID: client_12345
        X-Nonce: k3p9Qx
        X-Api-Signature: 96c25359bf61c9c5745403c6da4bb1c2838f7d1d40dbadab865f274023378927
        ```

        A missing header, an unknown client, a signature that does not match, or a signature that has already been used within the last hour returns `401` in the standard envelope (`success`, `message`, `data`, `errors`).

        ## Sign in with Google

        There are two sign-in options. The app can send an identity token from the Google SDK, or it can ask this API for a Google login URL and let the browser finish sign-in.

        Signed requests still need `X-Client-ID`, `X-Nonce`, and `X-Api-Signature`. A JSON body is part of the signature payload. The Google browser callback is not signed, because Google redirects the browser there.

        ### Google login URL

        `GET /api/auth/google/url`

        The response `data.url` is a Google authorization URL. Open it in the browser. Register `GOOGLE_REDIRECT_URI` (or `{APP_URL}/auth/google/callback` when that value is empty) as an authorized redirect URI on the Google OAuth client.

        After Google signs the user in, this API redirects the browser to an Android intent link. Set `GOOGLE_MOBILE_INTENT_SCHEME` and `GOOGLE_MOBILE_INTENT_PACKAGE` to the app scheme and application id. The app opens on `{scheme}://callback`.

        Success carries the Sanctum bearer token in `token`. Failure carries `error` instead, for example `invalid_state`, `access_denied`, or `sign_in_failed`.

        ```text
        intent://callback?token=1%7Cs3rbaJayaExampleToken#Intent;scheme=serbajaya;package=com.serbajaya.app;end
        ```

        The identity-token endpoint accepts the token the provider already gave the app and returns a Sanctum bearer token. It does not redirect.

        ### Google

        `POST /api/auth/google`

        ```json
        {
          "identity_token": "the Google ID token from the mobile SDK",
          "device_name": "iPhone 16"
        }
        ```

        `device_name` is optional and defaults to `mobile`. It only labels the Sanctum token.

        Web-based login uses `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`. The secret is only used to exchange Google's authorization code.

        SDK login does not use a client secret. Put each mobile client id in `GOOGLE_CLIENT_IDS`, separated by commas. The identity token audience must be one of those ids. `GOOGLE_CLIENT_ID` is also accepted.

        The account is stored by the provider subject id. A later sign-in with the same Google account returns a token for the same user, even when the provider omits the email.

        ### Response

        `data.token` is the Sanctum bearer token. Send it as `Authorization: Bearer {token}` on later requests that require a signed-in user. Those requests still require the API signature headers.

        ```json
        {
          "success": true,
          "message": "Signed in.",
          "data": {
            "token": "1|s3rbaJayaExampleToken",
            "user": {
              "id": 1,
              "name": "Buyer",
              "email": "buyer@example.com"
            }
          },
          "errors": null
        }
        ```

        ## Complete registration

        Google sign-in stores the name and email. `POST /api/auth/register` saves the rest for the signed-in user. Send the Sanctum bearer token and the API signature headers.

        The profile photo is not part of the signature. Sign the text fields only, exactly as sent.

        | Field | Required | Value |
        | --- | --- | --- |
        | `name` | yes | Display name. |
        | `phone` | yes | Indonesian mobile number. Spaces and dashes are removed. `0812...`, `+62...`, and a number with no prefix are stored as `628...`. |
        | `birth_date` | no | `Y-m-d`. |
        | `gender` | no | `male` or `female`. |
        | `profile_photo` | no | Image file, resized to 600 by 600 WebP. The response URL is built from the stored disk and path. |
        | `referral_code` | no | Another member's code. It is rejected when that code does not exist. |

        `POST /api/user/referral/code-check` accepts `{ "code": "K7MQ2P9" }` and returns `data.valid`. Use it to enable the submit button. The signed-in user's own code is not valid.

        The register response `referral_code` is this account's own code to share. `referral_code` in the request is the code they join with.

        An identity token that fails verification is rejected:

        ```http
        HTTP/1.1 401 Unauthorized
        Content-Type: application/json

        {
          "success": false,
          "message": "The identity token is invalid.",
          "data": null,
          "errors": {
            "identity_token": [
              "The identity token is invalid."
            ]
          }
        }
        ```
        MD;
    }
}
