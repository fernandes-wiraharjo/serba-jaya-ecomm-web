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

        Create the client under **Admin → API clients**. The secret key is shown once, when the client is created or regenerated. Outside production it starts with `sk_dev_`. In production it starts with `sk_prod_`.

        <div style="margin:0 0 1.25rem;padding:0.95rem 1rem;border:2px solid #d97706;border-radius:8px;background:#fff4cc;color:#7a3e00;line-height:1.5;"><b>Store the secret key securely and never send it with a request.</b> Only the client id is exposed, in the X-Client-ID header. The secret key stays on client's secure storage and is used only to build X-Api-Signature.</div>


        ## How to build the signature

        1. Collect the request payload: JSON body, form body, and query parameters. Headers are not part of the payload.
        2. Drop every field whose value is `null`.
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
        MD;
    }
}
