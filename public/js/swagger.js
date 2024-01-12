import SwaggerUI from "swagger-ui";
SwaggerUI({
    url: document.currentScript.getAttribute('url'),
    dom_id: '#swagger-ui',
});