function toggleEnable(event) {
    let enabled = event.target.checked;
    document
        .querySelectorAll("#enabledisable input, #enabledisable button, #enabledisable select")
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
        addAlert('alerts', 'Form is invalid', 'warning');
        return;
    }

    addAlert('alerts', 'Username or access token empty', 'warning');
    // test for empty
    const username = document.getElementById("mastodonUsername").value;
    const accesstoken = document.getElementById("mastodonAccessToken").value;
    if (!!!username || !!!accesstoken) {
        addAlert('alerts', 'Username or access token empty', 'warning');
        return;
    }

    // get domain
    const domain = username.match("@.*@(.*)")[1];
    if (!!!domain) {
        addAlert('alerts', 'Something went wrong parsing username', 'danger');
        return;
    }

    // start test
    document.getElementById("verifyButton").disabled = true;
    addAlert('alerts', 'Testing...', 'info');
    const now = new Date();
    let formData = new FormData();
    formData.append("status", 'Movary test post: ' + now.toISOString());
    formData.append("visibility", "private");
    const postUrl = "https://" + domain + "/api/v1/statuses";
    console.group("making a request to post a status to " + postUrl);

    let response;
    try {
        response = await fetch(postUrl, {
            method: 'post',
            headers: {
                Authorization: 'Bearer ' + accesstoken,
                "Idempotency-Key": now.toISOString(),
            },
            signal: AbortSignal.timeout(4000),
            body: formData,
        });
    } catch (error) {
        console.error(error);
        addAlert('alerts', 'Something went wrong with the request. Please see browser console.', 'danger');
    }
    console.log("got response", response);
    json = await response.json();
    console.log("got json", json);

    if (!response.ok) {
        addAlert('alerts', 'Server error: ' + json["error"], 'danger');
    } else {
        addAlert('alerts', `Success! See the test post here: <a href="${json["url"]}">${json["url"]}</a>`, 'success');
    }

    console.groupEnd();
    document.getElementById("verifyButton").disabled = false;
}

document.addEventListener("DOMContentLoaded", () => {
    document
        .getElementById("mastodonEnable")
        .addEventListener("change", toggleEnable);
});
