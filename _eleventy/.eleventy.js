const yaml = require("js-yaml");
const fs = require("node:fs");

module.exports = function (eleventyConfig) {
  // Content files are YAML so the CMS can write them in a human-readable form.
  eleventyConfig.addDataExtension("yml", (contents) => yaml.load(contents));

  // Uploaded photos and the CMS itself are copied through untouched.
  eleventyConfig.addPassthroughCopy({ images: "images" });
  eleventyConfig.addPassthroughCopy({ admin: "admin" });

  // Rebuild the page whenever content or uploads change.
  eleventyConfig.addWatchTarget("src/_data/");
  eleventyConfig.addWatchTarget("images/");

  return {
    dir: { input: "src", output: "_site", includes: "_includes", data: "_data" },
    htmlTemplateEngine: "njk",
    markdownTemplateEngine: "njk",
  };
};
