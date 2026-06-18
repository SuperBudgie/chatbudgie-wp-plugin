=== ChatBudgie - AI Chatbot based on ChatGPT, RAG and Knowledge Base ===
Contributors: superbudgie
Tags: ai chatbot, chat bot assistant, chatgpt, claude, customer support
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

AI chatbot for WordPress that answers visitor questions from your site content using RAG, ChatGPT, Claude, and local vector search.

== Description ==

ChatBudgie is an AI chatbot plugin for WordPress that turns your website content into an intelligent knowledge base. It helps visitors get fast, accurate answers from your posts and pages using RAG (Retrieval-Augmented Generation), local vector search, and managed AI models such as OpenAI ChatGPT, Claude, and Qwen.

Use ChatBudgie as a WordPress AI assistant, site search companion, product FAQ bot, documentation helper, or customer support chatbot. Once activated, it automatically indexes your public posts and pages, builds a local search index, and provides context-aware answers without complex setup.

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

= Can ChatBudgie answer questions from my WordPress content? =
Yes. ChatBudgie is designed to answer visitor questions using your public WordPress posts and pages. It retrieves relevant content from your local index before generating a response.

= Can I use ChatBudgie as a customer support chatbot? =
Yes. ChatBudgie can help visitors find answers from your website content, product information, documentation, FAQs, and support articles.

= Is my data safe? =
The vector index of the knowledge base is stored locally in this site's WordPress uploads directory.
Only public data of your website will be indexed.

= Can I customize the chat bubble icon? =
Yes! You can choose from several built-in icons or upload your own custom icon in the Appearance settings.

= Does it support real-time updates? =
Yes, whenever you publish or update a post, ChatBudgie automatically updates its index.

= Why should I buy tokens? =
Tokens are the fuel for ChatBudgie's AI capabilities. They are consumed when indexing your content (creating AI embeddings) and when answering user questions (generating LLM responses). By purchasing tokens from ChatBudgie, you get access to leading models like OpenAI ChatGPT and Claude without needing to manage separate API accounts or complex billing with multiple AI providers—we handle all that for you.

== External services ==

This plugin relies on the following external services:

= SuperBudgie platform =

ChatBudgie connects to the SuperBudgie platform (`https://chat.superbudgie.com/`) to authenticate the site, generate embeddings for indexed content, generate chat responses, show account and billing information, and create/capture token purchase orders.

Data sent to SuperBudgie depends on the feature being used:

* When an administrator connects the plugin to a SuperBudgie account, the plugin sends the OAuth callback code, the plugin app name, and the site URL to the SuperBudgie authentication service.
* When content is indexed, the plugin sends the post or page title, excerpt, content, content type, site URL, plugin app name, and the site's ChatBudgie app key to the SuperBudgie embedding service.
* When a visitor sends a chat message, the plugin sends the visitor's message, recent conversation history, the relevant content snippets retrieved from the local index, the site URL, and the site's ChatBudgie app key to the SuperBudgie chat service so it can generate a response.
* When an administrator opens the ChatBudgie account, usage, or orders screens, the plugin sends the site's ChatBudgie app key, plus pagination parameters for usage/orders requests, to the SuperBudgie account service.
* When an administrator buys tokens, the plugin sends the selected package, amount, currency, display price, site URL, order ID, the plugin app name, and the site's ChatBudgie app key to the SuperBudgie payment service.

SuperBudgie terms of service: https://chat.superbudgie.com/terms-of-service
SuperBudgie privacy policy: https://chat.superbudgie.com/privacy-policy

= PayPal =

ChatBudgie loads the PayPal JavaScript SDK in the plugin's admin orders screen and uses PayPal to let administrators complete token purchases.

Data sent to PayPal depends on the purchase flow:

* When the admin orders page is loaded, the administrator's browser connects to `https://www.paypal.com/sdk/js` to load the PayPal checkout script.
* When an administrator completes checkout, PayPal receives the payment/order information needed to process the transaction. Payment details are handled by PayPal, not stored by this plugin.

PayPal user agreement: https://www.paypal.com/us/legalhub/paypal/useragreement-full
PayPal privacy policy: https://www.paypal.com/us/legalhub/privacy-full

== Credits ==

ChatBudgie is built upon several high-quality open-source libraries:

* **Action Scheduler**: Robust background task processing for WordPress. Licensed under GPLv3.
* **Vektor**: High-performance local vector search engine. Licensed under MIT.

== Screenshots ==

1. ChatBudgie AI chatbot widget appearing on the WordPress frontend.
2. ChatBudgie admin dashboard showing knowledge base index status and token usage.
3. Appearance settings for customizing the chatbot widget colors and icons.

== Changelog ==

= 1.1.1 =
* Add multi language support, such as 智能聊天AI, Künstliche Intelligenz Chat, Robot de chat IA, Asistente virtual IA, 人工知能チャット

= 1.1.0 =
* Improved search result accuracy by grouping results by post.
* Enhanced frontend chat flow with optimized conversation history management.
* Refined welcome message logic based on account connection status.
* Improved security with consistent manual nonce checks and custom session expiry messages.
* Updated API integration to use refined data field names for better alignment with the backend.

= 1.0.0 =
* Initial release.
* Added RAG-based chat functionality.
* Integrated local Vektor search engine.
* Added PayPal payment support.
* Implemented background indexing via Action Scheduler.

== Upgrade Notice ==

= 1.1.1 =
Readme and metadata improvements for better plugin discoverability.

= 1.1.0 =
Improved search result accuracy, enhanced chat flow, and security updates. Recommended for all users.

= 1.0.0 =
Initial version. No upgrade notice required.
