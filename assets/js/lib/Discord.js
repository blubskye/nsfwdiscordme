export const DISCORD_API_URL = 'https://discord.com/api/v10';

/**
 *
 */
class Discord
{
  /**
   * Returns a boolean indicating whether the given value is a 64bit snowflake ID
   *
   * @param {string} snowflake
   * @returns {boolean}
   */
  static isSnowflake(snowflake) {
    return /^[\d]{17,}$/.test(snowflake);
  }

  /**
   * @param {string} serverID
   * @returns {Promise}
   */
  static fetchWidget(serverID) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: `${DISCORD_API_URL}/guilds/${serverID}/widget.json`
      }).done((resp) => {
        resolve(resp);
      }).fail((jqXHR) => {
        reject(jqXHR.status);
      });
    });
  }
}

export default Discord;
