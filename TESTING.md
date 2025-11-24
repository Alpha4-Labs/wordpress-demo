# Testing the Plugin

You can test this plugin instantly in your browser without installing WordPress, thanks to [WordPress Playground](https://wordpress.org/playground/).

## ⚡ Quick Start (Browser)

1.  **[Click here to launch a live demo](https://playground.wordpress.net/?plugin=https://api.loyalteez.app/loyalteez-api/wordpress-plugin.zip&url=/wp-admin/options-general.php?page=loyalteez-rewards)**
2.  This will:
    *   Spin up a temporary WordPress site in your browser (WASM).
    *   Automatically install and activate the **Loyalteez Rewards** plugin.
    *   Log you in as admin.
    *   Take you directly to the settings page.

## 🧪 How to Test

Once the playground loads:

1.  **Configure Brand ID**:
    *   Enter a test Brand ID (e.g., `0x123...`).
    *   Enable "Debug Mode".
    *   Click "Save Changes".

2.  **Test Frontend Share**:
    *   Go to "Visit Site" (hover over the home icon in the top left).
    *   Open your browser's Developer Tools (F12) -> Console.
    *   Run this snippet to simulate a share button:
        ```javascript
        var btn = document.createElement('button');
        btn.className = 'loyalteez-share';
        btn.innerText = 'Share for Rewards';
        document.body.appendChild(btn);
        ```
    *   Click the button that appears at the bottom of the page.
    *   Enter an email address when prompted.
    *   Watch the network request in the "Network" tab (it will fail with 404/500 in this isolated environment because it can't reach the real Loyalteez API, but you can verify the plugin *attempted* the call).

3.  **Test Comments**:
    *   Go to any post ("Hello World").
    *   Leave a comment.
    *   Approve the comment (if needed) in `wp-admin`.
    *   Check the `wp-content/debug.log` (Viewable in Playground via Tools -> Browser Storage or similar file managers if available) to see the API call attempt.

## 🐛 Limitations in Playground

*   **Network Requests**: The Playground runs in your browser. Requests to external APIs (like `api.loyalteez.app`) might be blocked by CORS policies depending on the browser and API configuration.
*   **Persistence**: Everything is deleted when you close the tab.

