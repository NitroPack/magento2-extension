The NitroPack extension for Magento brings the ultimate site speed and performance optimization package on the market to your Magento store. Increase pageviews, conversion rates, and revenue with automatic cache, image, and code optimization used by 163,000+ site owners and merchants worldwide.

Sign up today and get best-in-class caching, advanced image and code optimization, built-in CDN, lazy loading, and more.

NitroPack Homepage: www.nitropack.io

Installation Instructions: https://support.nitropack.io/hc/en-us/articles/12706205048081

# Release Notes

### 3.3.0:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description:
- Improvement: Added the option to include additional Varnish headers specific to certain hosting providers.
- Improvement: Use multiple Varnish instances on one Magento website.
- Improvement: Added a flag in system config to stop the invalidation process for each product, category, CMS page, and CMS block update.
- Improvement: Created a warning message for users with locked configuration for caching application.
- Improvement: Added DocBlocks in all PHP classes for better code documentation.
- Improvement: NitroPack now automatically connects to Redis if the Magento environment file contains specific cache settings.
- Improvement: Pre-select website on redirect from Magento plugin to the NitroPack App.
- Bug Fix: “Apply Automatic Fix” extension disable modal no longer appears.
- Bug Fix: Popup no longer appears when clicking on the selected mode.

### 3.2.1:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description:
- Improvement: Added notification for Varnish mismatch on the user's server and in Magento configuration.
- Improvement: Reverse Proxy now supports domain names.
- Bug Fix: Resolved duplicate tag API triggers for one URL.


### 3.2.0:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description:
- Improvement: Create CLI commands to purge cache by tags, products, categories, and URLs.
- Improvement: Code refactoring.
- Improvement: Enhance compatibility with Magento 2.4.7 and 2.3.
- Bug Fix: Fix issue where the NitroPack extension disconnects when the backlog.queue file exists and the NitroPack extension settings are accessed.
- Bug Fix: Resolve issue where the log file cannot be downloaded.

### 3.1.1:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description:
- Improvement: Enhanced handling of invalidation requests.

### 3.1.0:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description:
- Improvement: Enhanced observers for faster and better cache purging.
- Improvement: Consolidated sitemap into a single level for improved cache warmup.
- Improvement: Validate Redis configuration before enabling.
- Bug fix: No more temporary disconnects during Magento upgrades.


### 3.0.1:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: Bug Fix: Addressed a potential issue with Tailwind CSS during compilation for users who use CSS minification.


### 3.0.0:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: 
- Improvement: Implemented a UI Revamp for the NitroPack extension, making it easier to navigate and use.
- Improvement: Added support for multiple store views XML on cache warmup. Now, distinct XML files are generated for different store views, ensuring efficient caching.
- Improvement: Minor cache warmup improvements.
- Improvement: Added a log feature to the NitroPack extension, facilitating easier troubleshooting.



### 2.9.2:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: Improvements: Enhance the [ignore tag functionality](https://support.nitropack.io/en/articles/9144958-how-to-use-ignored-cache-tags-in-nitropack-for-magento-extensions) for tagging items with a specific pattern.

###   2.9.1:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: Improvements:  PHP 7.4 compatibility


###   2.9.0:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: Improvements:
- Improvement: Added integration for [Fastly CDN](https://support.nitropack.io/en/articles/9013996-how-to-configure-fastly-cdn-with-nitropack-for-optimal-magento-performance).
- Improvement: Code revamp for [Varnish integration](https://support.nitropack.io/en/articles/9014014-how-to-configure-varnish-with-nitropack-for-optimal-magento-performance).
- Bug fix: Resolved the issue where enabling Varnish caused the Full Page Cache option to change to NitroPack.


###   2.8.0:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: Improvements:
- [Ignore cache tags](https://support.nitropack.io/en/articles/9144958-how-to-use-ignored-cache-tags-in-nitropack-for-magento-extensions) - Users can enhance performance by specifying which cache tags to ignore during the purging process.
- Onboarding email - Introduction of an onboarding email for users who have not connected their extension.
- Code refactoring - removing the use of ObjectManager class and obsolete information parameters from the setting JSON that are no longer in use.

###   2.7.1:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: Fix:
- Bug Fix: This release returns to our previous page caching approach, allowing the entire page to be cached, even when a non-cacheable block is present.

###   2.7.0:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description:
Improvement:
- Our diagnostics have been enhanced to better identify why Magento cache tags might be missing, potentially causing issues with NitroPack.
- We brought back GZIP Compression with a clever feature that hides the setting if it is already in use, ensuring smoother operation and preventing accidental activation.

###   2.6.1:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description:
Improvement:
- Refined API secret and API key labels to enhance clarity.
- Strengthened security by upgrading encryption protocols for X-Magento-Vary header.
- Implemented technical enhancements in NitroPack scripts.

###   2.6.0:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: 
Improvement: 
- Users now have the ability to control whether their NitroPack cache will be purged upon specific product attribute changes.
- Bug Fix: Addressed an issue when the default Magento cache is flushed.
- Bug Fix: Cache Warmup status is now accurately synchronized with the NitroPack settings.



###   2.5.3:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: Fix:
- In this update, we’ve implemented checks to ensure the existence of specific classes and variables within Magento, enhancing the functionality of NitroPack.


###   2.5.2:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: Fix:
- Fixed Redis cache flush issue, ensuring reliable cache management.
- Added a condition in product attribute change checks to enhance efficiency.

###   2.5.1:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: Improvements: Our cache clear webhook now supports purging multiple URLs, offering enhanced efficiency and flexibility for cache management.

###   2.5.0:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: Improvements:
- Non-zero quantity changes to a product will no longer trigger page invalidation. 
- Enhanced notifications for various extension events.


###   2.4.7:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: Fix:
- The end users will not see an error message or broken NitroPack banner at the bottom of the site (footer) in case of NitroPack API disconnection.


###   2.4.6:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: Improvements:
- New support widget

###   2.4.5:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: Improvements:
- SDK Version Update: We've updated the SDK, ensuring a more reliable performance. 
- Improved Notification: We've introduced a new notification system to keep you informed. You'll receive notifications when Test Mode (formerly "Safe Mode") is enabled, giving you better control and system status monitoring.
- Issue Resolution: Blank Event on NitroPack - Addressed the NitroPack issue where a blank event was mistakenly triggering. Enjoy a smoother NitroPack experience. 
- Optimized Varnish Configuration: We've fine-tuned the Varnish configuration process to trigger the Varnish Configure API at the right time, ensuring accuracy after every page optimization and invalidation.


###   2.4.3:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: Improvements:
-  Bug Fixes and compatibility improvements for Magento 2.3 and Timeout Error fix for invalid URLs  


###   2.4.2:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: Improvements:
- Config webhooks improvements


###   2.4.1:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description: Improvements:
-  Improved proxy server configuration process with new setting fields, error detection, and improved communication with the main NitroPack App
-  Automatic CRON execution in case of cache purge
-  Additional notifications in different cases related to NitroPack automatic or manual  disabling

###   2.4.0:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description:
-  New feature: Enhanced caching with caching variations - NitroPack will cache different versions of a page based on customer group, selected store, currency, language, and customer logged in or not

###   2.3.3:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description:
- Improved Store View optimization: new observers have been incorporated to enhance the handling of cookie variations for store groups containing multiple store views. This observer ensures that the appropriate cookie variations are added based on the specific store view being accessed. 
- Improved NitroPack behavior according to different Crons statuses


###   2.3.1:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description:
- A new check is added before to clear the tags when NitroPack has been connected.

###   2.3.0:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description:

- Tagging System Improvements: The new version of NitroPack includes improvements to the tagging system, specifically implementing Magento native tags. This enhancement allows for better management and control of caching based on Magento's native tagging system.
- Improved Varnish Compatibility: URLs excluded from NitroPack caching (by using the NitroPack Exclude URLs feature) are now cacheable by Varnish. This improvement ensures more seamless [integration between NitroPack and Varnish caching](https://support.nitropack.io/en/articles/9014014-how-to-configure-varnish-with-nitropack-for-optimal-magento-performance).
- Improved Varnish Compatibility: Varnish invalidation process updated. With this update, the Varnish invalidation process now depends on the system configuration, allowing it to work seamlessly based on the specific configuration settings. This ensures more accurate and effective invalidation of the Varnish cache when necessary.
- NitroPack Safe Mode Integration: The new version introduces the integration of NitroPack Safe Mode. Safe Mode is a feature that allows users to easily enable or disable NitroPack optimization functionality without affecting the website's performance. This integration gives users greater flexibility and control over NitroPack caching and optimization features.
- Cacheable Third-Party Custom URLs: The new version of NitroPack introduces the ability to cache all third-party custom URLs. This enhancement allows NitroPack to effectively cache and optimize not only the website's own content but also custom URLs from third-party integrations, providing a more comprehensive caching solution

###   2.2.3:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description:
Fixes and improvements
* Using a port other than 80 for Varnish is now possible
* Correct behavior of headers indicating the browsers not to cache checkout, cart, etc. pages
* Improvements in the communication between NitroPack and the Varnish caching layer

### 2.2.1:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description:

This release contains the following features:
* Webhook token for additional security URLs can be accessible for connected Magento instance
* Generate a Diagnostics Report file (verifying whether the required configuration is configured or not, helpful for support cases)
* Automatically disable the NitroPack extension if the website is in Maintenance mode

### 2.1.7:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description:
#### Bugfixes and improvements:
- Fixed Reverse Proxy configuration issue when it was forcefully disabled via the NitroPack app dashboard
- In case of Varnish server misconfiguration, no optimized pages will be served from Varnish but from NitroPack storage
- NitroPack Extension Dashboard will notify about the need for Cron enabled for the health check if a problem has been detected

### 2.1.6:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description:
Due to the parent category of the store view added on product change, every page is purged, which is not the correct behavior fixed

### 2.1.5:
Compatible with Magento Open Source : 2.4
Stability: Stable Build
Description:
Fix the NitroPack SDK change issue add parameter in a function

### 2.1.3:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description:
The queue issue is fixed for the Magento MySQL queue to check for different Magento versions, i.e 2.3 , 2.4.3,2.4.5 and 2.4.6

### 2.1.2:
#### Compatible with Magento Open Source : 2.4
#### Stability: Stable Build
#### Description:
init-release
