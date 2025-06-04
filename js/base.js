function formToJSON(form) {
  const formData = new FormData(form);
  const json = {};

  formData.forEach((value, key) => {
    if (key.includes("[")) {
      const keys = key.split(/\[|\]/).filter(Boolean);
      let current = json;

      keys.forEach((nestedKey, index) => {
        if (!current[nestedKey]) {
          current[nestedKey] = isNaN(keys[index + 1]) ? {} : [];
        }
        if (index === keys.length - 1) {
          // Only assign if value is not null or empty
          if (value !== null && value !== "") {
            current[nestedKey] = value;
          }
        }
        current = current[nestedKey];
      });
    } else {
      // Simple fields
      if (value !== null && value !== "") {
        json[key] = value;
      }
    }
  });

  // Remove null/undefined values from arrays (e.g., addons, discounts)
  const cleanArrays = (obj) => {
    Object.keys(obj).forEach((key) => {
      if (Array.isArray(obj[key])) {
        obj[key] = obj[key].filter(
          (item) =>
            item !== null && item !== undefined && Object.keys(item).length > 0
        );
      } else if (typeof obj[key] === "object") {
        cleanArrays(obj[key]);
      }
    });
  };

  cleanArrays(json);
  return json;
}