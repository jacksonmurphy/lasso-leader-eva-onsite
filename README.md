# Lasso Leader (by Jackson Murphy)

> A custom WordPress plugin designed to serve as a robust bridge between your website's forms (Gravity Forms & Contact Form 7) and the Lasso CRM.

## Description

Lasso Leader is a purpose-built integration that captures form submissions and transmits that data to the correct projects and agents within Lasso CRM. It includes a user-friendly settings interface within the WordPress admin to manage the connection, as well as advanced, per-form controls for complex routing and data mapping. The plugin correctly handles the submission of both standard registrant data and complex custom questions via Lasso's two-step API process. It also includes a module for injecting Lasso's frontend analytics tracking script.

## Core Features

* **Global Settings:** A centralized dashboard to manage global settings, including a primary Lasso API Key, a default Project ID, and settings for add-on modules.
* **Gravity Forms Integration:**
    * Enable integration on a form-by-form basis.
    * Per-form overrides for the API Key and Project ID.
    * A detailed visual interface to map form fields to standard Lasso fields or to specific Custom Questions with unique Question and Answer IDs.
    * Correctly uses Lasso's two-step API process to first create a registrant and then submit answers to custom questions.
* **Contact Form 7 Integration:**
    * Enable integration for specific forms via a checklist on a dedicated settings page.
    * A full visual interface to map form fields to standard Lasso fields or Custom Questions, eliminating the need for custom code snippets.
    * Correctly uses Lasso's two-step API process.
* **Frontend Analytics Tracking:**
    * Injects the Lasso Analytics tracking script across the website.
    * Supports a global Lasso Analytics Account ID.
    * Allows for page-specific Account ID overrides.
    * Supports a page exclusion list to disable tracking where needed.
* **Automatic Plugin Updates:**
    * Includes a built-in update checker that uses the private GitHub repository as an update server.
    * Provides standard "Update now" notifications within the WordPress admin panel for seamless, one-click updates.

## Installation & Setup Guide

### Step 1: Global Settings

These settings control the default behavior for the entire site.

1.  In the WordPress admin, navigate to **Lasso Leader > Global Settings**.
2.  Enter your primary **Lasso API Key**.
3.  Enter the **Default Project ID**. This will be used for any form that does not have a specific override.
4.  Click **"Save Global Settings"**.

### Step 2: Form Integration & Field Mapping

Configure each form builder integration as needed.

#### Contact Form 7

1.  **Enable Forms:** Navigate to **Lasso Leader > Contact Form 7**. You will see a list of all your CF7 forms. Check the box next to each form you wish to integrate with Lasso.
2.  **Save Changes:** Click "Save CF7 Settings". The page will reload.
3.  **Map Fields:** A **"Configure Mapping"** button will now appear next to each enabled form. Click it to go to the mapping page for that form.
4.  **Configure Mappings:**
    * **For Standard Fields** (e.g., `contact_first_name`): Select the corresponding Lasso field (e.g., "First Name") from the "Map to Standard Lasso Field" dropdown.
    * **For Custom Questions** (e.g., `hear`): Leave the "Standard Field" dropdown blank. On the right, enter the numeric **Question ID** and select the **Type** (`Answer ID` for dropdowns/checkboxes, `Text` for text fields).
5.  Click **"Save Mappings"**.

#### Gravity Forms

The process is very similar and is handled within the Gravity Forms settings for each form.

1.  Navigate to the form you wish to configure, then go to its **Settings > Lasso Leader**.
2.  Check the box to **"Enable Integration"**.
3.  (Optional) Fill in the **API Key (Override)** or **Project ID (Override)** fields if this form needs to submit to a different place than the global default.
4.  **Configure Mappings:** In the "Custom Field Mapping" table, configure your fields just as you would for Contact Form 7.
5.  Click **"Save Settings"**.

### Step 3: Frontend Analytics Tracking

These settings are located on the **Lasso Leader > Global Settings** page.

1.  **Enable Frontend Tracking:** Check this box to activate the tracking script.
2.  **Lasso Analytics Account ID (Global):** Enter your main tracking ID here.
3.  **Custom Tracking Page IDs & Account IDs:** For special pages that need a different tracking ID. Enter one per line in the format `PageID=LAS-XXX-YY`.
    * *Example:* `123=LAS-111-222`
4.  **Pages to Exclude from Tracking:** Enter the numeric Page IDs for any pages where the tracking script should be completely disabled (e.g., thank you pages). You can separate IDs with a comma or a new line.

## Planned Future Enhancements

* **On-Site Registration Add-On:**
    * **Goal:** To rebuild and simplify the existing "On-Site Registration" system.
    * **Plan:** This module, when enabled, will add "Projects" and "Agents" management areas to the WordPress admin. A simple shortcode (`[lasso_onsite_directory]`) will then be used to dynamically generate a project/agent selection page, streamlining the entire on-site registration process.
* **Multi-Project Submissions:**
    * **Goal:** To enhance the form integrations so that a single form submission can create leads in multiple Lasso projects based on which checkboxes a user selects.