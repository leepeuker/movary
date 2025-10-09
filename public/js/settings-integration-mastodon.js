function toggleEnable(event) {
  let enabled = event.target.checked;
  document
    .querySelectorAll("#enabledisable input, #enabledisable button")
    .forEach((el) => {
      if (enabled) {
        el.removeAttribute("disabled");
      } else {
        el.setAttribute("disabled", "");
      }
    });
  if (enabled)
    document.getElementById("enabledisable").classList.remove("disabled");
  else document.getElementById("enabledisable").classList.add("disabled");
}

async function testCredentials() {
  // test form valid
  if (!document.getElementById("mastodonForm").reportValidity()) {
    addAlertMessage("Form is invalid", "warning");
    return;
  }

  // test for empty
  const username = document.getElementById("mastodonUsername").value;
  const accesstoken = document.getElementById("mastodonAccessToken").value;
  if (!!!username || !!!accesstoken) {
    addAlertMessage("Username or access token empty", "warning");
    return;
  }

  // get domain
  const domain = username.match("@.*@(.*)")[1];
  if (!!!domain) {
    addAlertMessage("Something went wrong parsing username", "danger");
    return;
  }

  // start test
  document.getElementById("verifyButton").disabled = true;
  addAlertMessage("testing…", "info");
  const now = new Date();
  let formData = new FormData();
  formData.append("status", "Movary test post: " + now.toISOString());
  formData.append("visibility", "private");
  const postUrl = "https://" + domain + "/api/v1/statuses";
  console.group("making a request to post a status to " + postUrl);

  let response;
  try {
    response = await fetch(postUrl, {
      method: "post",
      headers: {
        Authorization: "Bearer " + accesstoken,
        "Idempotency-Key": now.toISOString(),
      },
      body: formData,
    });
  } catch (error) {
    console.error(error);
    addAlertMessage(
      "Something went wrong with the request. Please see browser console.",
      "danger"
    );
  }
  console.log("got response", response);
  json = await response.json();
  console.log("got json", json);

  if (!response.ok) {
    addAlertMessage("Got error: " + json["error"], "danger");
  } else {
    addAlertMessage(
      `Success! See the test post here: <a href="${json["url"]}">${json["url"]}</a>`,
      "success"
    );
  }

  console.groupEnd();
  document.getElementById("verifyButton").disabled = false;
}

function addAlertMessage(message, type) {
  document.getElementById("alerts").innerHTML = [
    `<div class="alert alert-${type} alert-dismissible" role="alert">`,
    `   <div>${message}</div>`,
    '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
    "</div>",
  ].join("");
}

document.addEventListener("DOMContentLoaded", () => {
  document
    .getElementById("mastodonEnable")
    .addEventListener("change", toggleEnable);
});
