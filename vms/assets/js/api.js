/**
 * Centralized API Client
 * Visitor Management System (VMS)
 */

const Api = {
  /**
   * Performs an HTTP request against the VMS REST API.
   *
   * @param {string} url
   * @param {object} options
   * @returns {Promise<any>}
   */
  async request(url, options = {}) {
    const config = {
      method: options.method || 'GET',
      headers: {
        'Accept': 'application/json',
        ...(options.headers || {})
      }
    };

    if (options.body) {
      if (options.body instanceof FormData) {
        config.body = options.body;
      } else {
        config.headers['Content-Type'] = 'application/json';
        config.body = JSON.stringify(options.body);
      }
    }

    try {
      const response = await fetch(url, config);
      let json;

      try {
        json = await response.json();
      } catch (parseErr) {
        throw new Error(`Server returned unexpected response (${response.status})`);
      }

      if (!response.ok || json.success === false) {
        const errorMsg = json.message || `Request failed with status ${response.status}`;
        const err = new Error(errorMsg);
        err.status = response.status;
        err.data = json;
        throw err;
      }

      return json;
    } catch (err) {
      console.error('API Error:', err);
      throw err;
    }
  },

  get(url) {
    return this.request(url, { method: 'GET' });
  },

  post(url, body) {
    return this.request(url, { method: 'POST', body });
  },

  put(url, body) {
    return this.request(url, { method: 'PUT', body });
  },

  delete(url, body) {
    return this.request(url, { method: 'DELETE', body });
  }
};
