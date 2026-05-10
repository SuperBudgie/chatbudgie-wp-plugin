=== ChatBudgie ===
Contributors: superbudgie
Tags: chat, ai, chatbot, chat bot, chatgpt, claude, customer service, artificial-intelligence
Requires at least: 5.8
Tested up to: 6.9.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

ChatBudgie is a powerful, AI-driven chat plugin for WordPress that can automatically transform your website into an intelligent knowledge base.

== Description ==

ChatBudgie is an AI-powered chat plugin that transforms your WordPress website into an intelligent knowledge base. It provides a RAG (Retrieval-Augmented Generation) based agent that can answer your visitors' questions using your site's actual content.

Designed as a true out-of-the-box solution, ChatBudgie is incredibly easy to use. Once activated, it automatically handles the indexing of your posts and pages and building a local search index without any complex configuration. ChatBudgie understands your content and provides context-aware responses, ensuring your visitors get the help they need.

= Smart Chat =
* **Knowledge Base Answers**: Delivers accurate, easy-to-understand responses based on your website's actual content.
* **Modern AI Integration**: Powered by leading models like OpenAI ChatGPT, Claude, Qwen and etc for intelligent conversations.
* **RAG Technology**: Uses Retrieval-Augmented Generation to ensure AI stays grounded in your data.

= Easy to set up =
* **Seamless Integration**: Connect your SuperBudgie account in seconds and you're ready to go.
* **Visual Customizer**: Easily match the chat widget to your site's brand with color and icon settings.
* **Auto Indexing**: Knowledge base indexing runs automatically in the background, you don't have to manually manage it.

= Zero Maintenance =
* **Auto-Maintained Index**: Your knowledge base index is automatically updated whenever content changes or new posts are added, ensuring your chatbot always has the latest info.
* **Managed AI Models**: ChatBudgie handles the selection and configuration of leading AI models, so you don't have to worry about technical details.
* **Unified Token Billing**: Simply pay for ChatBudgie tokens; we manage the complex relationships and payments with AI providers.

== How it works ==

ChatBudgie bridges the gap between your website content and AI through a sophisticated workflow:

1. **Local Knowledge Base**: The plugin scans your WordPress posts and pages, breaking them into semantic "chunks."
2. **AI Embedding**: It calls superbudgie embedding API to select advanced AI models and transform these chunks into high-dimensional vector embeddings, which are stored securely in a **local** database on your server.
3. **Smart Search**: When a user asks a question, ChatBudgie performs a local vector search to find the most relevant information from your local index.
4. **LLM Generation**: The user query with retrieved context is sent to superbudgie chat API to select a Large Language Model (LLM) like GPT or Claude to generate a precise, human-like response, ensuring the answer is always grounded in your site's actual data.

== Installation ==

1. Install and activate the plugin through the 'Plugins' menu in WordPress.
2. Navigate to the **ChatBudgie** menu in your admin sidebar.
3. Log in SuperBudgie account to authenticate your site and receive some free tokens.
4. The plugin will automatically start indexing your content in the background. After that you can talk to your chatbot on your site.

== Frequently Asked Questions ==

= How does ChatBudgie index my site? =
ChatBudgie uses Action Scheduler to process your posts and pages in the background. It breaks the text into chunks and generates vector embeddings for each chunk.

= Is my data safe? =
The vector index of knowledge base is stored locally in the `/wp-content/plugins/chatbudgie/data/` directory.
Only public data of your website will be indexed.

= Can I customize the chat bubble icon? =
Yes! You can choose from several built-in icons or upload your own custom icon in the Appearance settings.

= Does it support real-time updates? =
Yes, whenever you publish or update a post, ChatBudgie automatically updates its index.

= Why should I buy tokens? =
Tokens are the fuel for ChatBudgie's AI capabilities. They are consumed when indexing your content (creating AI embeddings) and when answering user questions (generating LLM responses). By purchasing tokens from ChatBudgie, you get access to leading models like OpenAI ChatGPT and Claude without needing to manage separate API accounts or complex billing with multiple AI providers—we handle all that for you.

== Credits ==

ChatBudgie is built upon several high-quality open-source libraries:

* **Action Scheduler**: Robust background task processing for WordPress. Licensed under GPLv3.
* **Vektor**: High-performance local vector search engine. Licensed under MIT.

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

