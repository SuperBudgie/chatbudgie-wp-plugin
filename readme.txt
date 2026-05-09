=== ChatBudgie ===
Contributors: superbudgie
Tags: chat, ai, rag, vector-search, chatbot, customer-service, artificial-intelligence, machine-learning
Requires at least: 5.8
Tested up to: 6.9.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

ChatBudgie is a powerful, AI-driven chat plugin for WordPress that provides a RAG (Retrieval-Augmented Generation) based agent to answer website-related questions.

== Description ==

ChatBudgie is a powerful, AI-driven chat plugin for WordPress that provides a RAG (Retrieval-Augmented Generation) based agent. It uses a local vector search engine to index your website's content and deliver accurate, context-aware answers to your visitors.

Unlike traditional chatbots that rely on pre-defined scripts, ChatBudgie understands your site's actual content. It breaks down your posts and pages into small chunks, generates embeddings, and stores them in a local vector database. When a user asks a question, it finds the most relevant information and provides a natural language response.

= Key Features =

* **RAG-Based Agent**: Provides intelligent answers based on your actual website content.
* **Local Vector Search**: Utilizes a high-performance, local HNSW-based vector search engine (Vektor) for fast information retrieval.
* **Responsive Design**: Modern, customizable chat widget optimized for both desktop and mobile users.
* **Deep Customization**: Adjust primary/secondary colors, welcome messages, and choose from multiple chat icons.
* **Real-time Indexing**: Automatically updates the search index when you create, edit, or delete posts and pages.
* **Account & Usage Tracking**: Monitor your token usage and manage your account directly from the WordPress admin.
* **Seamless Top-ups**: Integrated PayPal support for purchasing additional token packages.
* **Background Processing**: Uses Action Scheduler for efficient, non-blocking background indexing tasks.

== Installation ==

1. Upload the `chatbudgie-wp-plugin` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the **ChatBudgie** menu in your admin sidebar.
4. Log in with your SuperBudgie account to authenticate your site.
5. The plugin will automatically start indexing your content in the background.

== Frequently Asked Questions ==

= How does ChatBudgie index my site? =
ChatBudgie uses Action Scheduler to process your posts and pages in the background. It breaks the text into chunks and generates vector embeddings for each chunk.

= Where is the data stored? =
The vector index is stored locally in the `/wp-content/plugins/chatbudgie-wp-plugin/data/` directory.

= Can I customize the chat bubble icon? =
Yes! You can choose from several built-in icons or upload your own custom icon in the Appearance settings.

= Does it support real-time updates? =
Yes, whenever you publish or update a post, ChatBudgie automatically updates its index.

== Screenshots ==

1. The chat widget appearing on the frontend.
2. The admin dashboard showing index status and usage.
3. Appearance settings for customizing colors and icons.

== Changelog ==

= 1.0.0 =
* Initial release.
* Added RAG-based chat functionality.
* Integrated local Vektor search engine.
* Added PayPal payment support.
* Implemented background indexing via Action Scheduler.

== Upgrade Notice ==

= 1.0.0 =
Initial version. No upgrade notice required.
