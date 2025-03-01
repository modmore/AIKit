# AI Kit for MODX

> This project is a WORK IN PROGRESS and not everything in this readme is implemented. Join us at the SnowUp to help shape the project!

A powerful and extendable AI toolkit for MODX 3, powered by ChatGPT (with plans for Gemini, and others).

AI Kit provides an AI Assistant in the MODX Manager that can answer questions, generate content, and more. It can be seamlessly integrated into MODX Extras providing more contextual assistance. Extras can either provide custom functions for the assistant to use, and/or invoke the assistant contextually.

Example use cases:

- Generate content using consistent instructions across the site and functions
- Create new resources from natural language
- Quickly navigate to specific resources, users, orders, etc
- Answer basic questions 

## Installing from source

- Clone the repository into a development site
- Copy config.core.sample.php in the root of the project and make sure it points to your core directory
- Run `composer install` in the `core/components/aikit/` directory
- Run `composer install` in the project root directory
- Run `composer bootstrap` or `php _bootstrap/index.php` in the project root directory
- Add your OpenAI api key to the `aikit.openai_api_key` system setting, or configure for your other OpenAPI compatible LLM.

## Installing package

Currently a transport package of the latest dev build is available from the _packages directory on github and [in the MODX.com package provider](https://extras.modx.com/package/aikit). 

## AI Assistant

The AI assistant lives in the manager, as an icon in the top-right of the manager. Clicking it will open a UI that slides out of the left side of the menu. From there users can access their own historic chats, start a new chat, and find a link to the configuration CMP. 

When triggering the AI assistant from a certain context (for example in a rich text editor like Redactor, to generate content to place in that specific field), that creates a new chat with the additional context and instructions baked in, and offering a quick button to accept the result [to do].

## DB Architecture

Chats are persisted to the database. Each chat is a Conversation object with many Message objects. Per the way LLM models are organised, there are developer, user, and model messages as part of a conversation.

## 

## Compiling Assets

All assets are in assets/components/aikit/ - both sources and dist files. 
