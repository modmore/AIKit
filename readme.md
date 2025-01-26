# AI Kit for MODX

A powerful and extendable AI toolkit for MODX 3, powered by ChatGPT, Gemini, and others.

AI Kit provides an AI Assistant in the MODX Manager that can be seamlessly integrated into MODX Extras. Extras can either provide custom functions for the assistant to use, and/or invoke the assistant to generate content.  

Example use cases:

- Generate content, for example from Redactor or ContentBlocks, using the same consistent instructions
- Create new resources from natural language
- Quickly navigate to specific resources, users, orders, etc
- Answer basic questions 

## AI Assistant

The AI assistant lives in the manager, as an icon in the top-right (?) of the manager. Clicking it will open a UI that slides out of the right side (?) of the manager. From there users can access their historic chats, start a new chat, and learn more about its capabilities.

When triggering the AI assistant from a certain context (like Redactor, to generate content to place in that specific field), that creates a new chat with that context. 

## DB Architecture

Chats are persisted to the database. Each chat is a Conversation object with many Message objects. Per the way LLM models are organised, there are system, user, and model messages as part of a conversion.

## 

## Compiling Assets

All assets are in assets/components/aikit/ - both sources and dist files. 

First make sure dependencies are installed with `npm install` (in the assets/components/aikit/ directory).

Next, `npm run <command>` to compile the various assets. For example:

- `npm run build:js`
- `npm run build:css`
- `npm run watch:js`
- `npm run watch:css`

Minified files (including source map) go into the `dist/` directory.
