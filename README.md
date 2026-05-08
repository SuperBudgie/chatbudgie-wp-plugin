# ChatBudgie WordPress Plugin

ChatBudgie is a powerful, AI-driven chat plugin for WordPress that provides a RAG (Retrieval-Augmented Generation) based agent. It uses a local vector search engine to index your website's content and deliver accurate, context-aware answers to your visitors.

## Key Features

- 🤖 **RAG-Based Agent**: Provides intelligent answers based on your actual website content.
- ⚡ **Local Vector Search**: Utilizes a high-performance, local HNSW-based vector search engine (Vektor) for fast information retrieval.
- 📱 **Responsive Design**: Modern, customizable chat widget optimized for both desktop and mobile users.
- 🎨 **Deep Customization**: Adjust primary/secondary colors, welcome messages, and choose from multiple chat icons (or upload your own).
- 🔄 **Real-time Indexing**: Automatically updates the search index when you create, edit, or delete posts and pages.
- 📊 **Account & Usage Tracking**: Monitor your token usage and manage your account directly from the WordPress admin.
- 💳 **Seamless Top-ups**: Integrated PayPal support for purchasing additional token packages.
- ⚡ **Background Processing**: Uses Action Scheduler for efficient, non-blocking background indexing tasks.

## Installation

1. **Upload Plugin**: Download the plugin and upload the `chatbudgie-wp-plugin` folder to your `/wp-content/plugins/` directory.
2. **Activate**: Go to the 'Plugins' menu in WordPress and activate **ChatBudgie**.
3. **Login**: Navigate to the **ChatBudgie** menu in your admin sidebar. You will be redirected to the SuperBudgie login page to authenticate your site.
4. **Indexing**: Upon activation and login, ChatBudgie will automatically start indexing your public posts and pages in the background.

## Configuration

### Appearance Settings
Customize the look and feel of your chat widget:
- **Primary/Secondary Colors**: Match the widget to your site's branding.
- **Welcome Message**: Set a friendly initial greeting for your users.
- **Custom Icon**: Choose from built-in icons or upload a custom SVG/PNG/JPG.

### Index Management
- **Manual Rebuild**: Trigger a full re-indexing of your site content if needed.
- **Real-time Updates**: The index stays in sync automatically as you manage your content.

## Technical Details

### RAG & Vector Search
ChatBudgie implements a local **HNSW (Hierarchical Navigable Small World)** graph for efficient vector similarity search. When a user asks a question, the plugin:
1. Generates an embedding for the query.
2. Performs a similarity search against the local vector store.
3. Retrieves relevant content "chunks" from your WordPress database.
4. Sends the context and query to the RAG API to generate a precise response.

### Requirements
- **PHP**: 7.4 or higher
- **WordPress**: 5.8 or higher
- **SSL**: Recommended for API communication

### Tech Stack
- **Backend**: PHP, WordPress API
- **Frontend**: Vanilla JavaScript, CSS3, jQuery
- **Vector Engine**: Custom PHP-based HNSW implementation (Vektor)
- **Background Tasks**: Action Scheduler

## Project Structure
```
chatbudgie/
├── chatbudgie.php          # Main plugin entry point
├── lib/
│   ├── action-scheduler/   # Background task management
│   └── Vektor/             # Local vector search engine
├── templates/              # Admin and widget UI templates
├── assets/
│   ├── css/                # Plugin stylesheets
│   └── js/                 # Frontend and admin scripts
├── data/                   # Local vector index storage
└── tests/                  # PHPUnit test suite
```

## Contributing
We welcome contributions! Please feel free to submit Issues or Pull Requests on our [GitHub repository](https://github.com/SuperBudgie/chatbudgie-wp-plugin).

## License
GPL v3 or later.

## Contact
- **Author**: SuperBudgie Team
- **Website**: [superbudgie.com](https://chat.superbudgie.com)
