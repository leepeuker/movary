name: Bug Report
description: Report a problem
labels: ['type:bug']
body:
- type: markdown
  attributes:
  value: |
  Thanks for taking the time to fill out this bug report!

      Please note that GitHub issues are exclusively for bug reports and feature requests. For support, please use our other support channels to get help.
- type: textarea
  id: description
  attributes:
  label: Description
  description: Please provide a clear and concise description of the bug or problem.
  validations:
  required: true
- type: input
  id: version
  attributes:
  label: Version
  description: What version of Movary are you running? (You can find this in Settings → App -> About)
  validations:
  required: true
- type: textarea
  id: repro-steps
  attributes:
  label: Steps to Reproduce
  description: Please tell us how we can reproduce the bug.
  placeholder: |
  1. Go to [...]
  2. Click on [...]
  3. Scroll down to [...]
  4. See error in [...]
  validations:
  required: true
- type: textarea
  id: screenshots
  attributes:
  label: Screenshots
  description: If applicable, please provide screenshots depicting the problem.
- type: textarea
  id: logs
  attributes:
  label: Logs
  description: Please copy and paste any relevant log output. (Will be automatically formatted, no need for backticks)
  render: shell
- type: dropdown
  id: platform
  attributes:
  label: Platform
  options:
  - Desktop
  - Smartphone
  - Tablet
  validations:
  required: true
- type: input
  id: device
  attributes:
  label: Device
  description: e.g., iPhone X, Surface Pro, Samsung Galaxy Tab
  validations:
  required: true
- type: input
  id: os
  attributes:
  label: Operating System
  description: e.g., iOS 8.1, Windows 10, Android 11
  validations:
  required: false
- type: input
  id: browser
  attributes:
  label: Browser
  description: e.g., Chrome, Safari, Edge, Firefox
  validations:
  required: false
- type: textarea
  id: additional-context
  attributes:
  label: Additional Context
  description: Please provide any additional information that may be relevant or helpful.
