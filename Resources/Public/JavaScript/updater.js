/**
 * Module: @twitch/updater.js
 */


import AjaxRequest from "@typo3/core/ajax/ajax-request.js"
import Notification from "@typo3/backend/notification.js";
import nprogress from "nprogress";


class Updater {
  constructor() {
    document.querySelectorAll('.t3js-filelist-twitch').forEach((item) => {
      item.addEventListener('click', (event) => {
        this.update(event);
      })
    })
  }

  update(event) {
    const url = TYPO3.settings.ajaxUrls.ayacoo_twitch_online_media_updater;
    const filename = event.currentTarget.dataset.filename
    const payload = {
      uid: event.currentTarget.dataset.fileUid
    }

    nprogress.start();
    new AjaxRequest(url)
      .post(payload).then(async function (response) {
      const data = await response.resolve();

      Notification.success(
        TYPO3.lang['twitch.alert.success'],
        TYPO3.lang['twitch.alert.success.text'] + ' ' + filename
      );
      document.location.reload();
    }, function (error) {
      Notification.error(TYPO3.lang['twitch.alert.error'], error.response.status + ' ' + error.response.statusText);
    });
  }
}

export default new Updater();
