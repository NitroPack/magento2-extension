The NitroPack extension for Magento brings the ultimate site speed and performance optimization package on the market to your Magento store. Increase pageviews, conversion rates, and revenue with automatic cache, image, and code optimization used by 163,000+ site owners and merchants worldwide.

Sign up today and get best-in-class caching, advanced image and code optimization, built-in CDN, lazy loading, and more.

NitroPack Homepage: www.nitropack.io 

Installation Instructions: https://support.nitropack.io/hc/en-us/articles/12706205048081

# Release Notes

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
- Improved Varnish Compatibility: URLs excluded from NitroPack caching (by using the NitroPack Exclude URLs feature) are now cacheable by Varnish. This improvement ensures more seamless integration between NitroPack and Varnish caching.
- Improved Varnish Compatibility: Varnish invalidation process updated. With this update, the Varnish invalidation process now depends on the system configuration, allowing it to work seamlessly based on the specific configuration settings. This ensures more accurate and effective invalidation of the Varnish cache when necessary.
- NitroPack Safe Mode Integration: The new version introduces the integration of NitroPack Safe Mode. Safe Mode is a feature that allows users to easily enable or disable NitroPack optimization functionality without affecting the website's performance. This integration gives users greater flexibility and control over NitroPack caching and optimization features. 
- Cacheable Third-Party Custom URLs: The new version of NitroPack introduces the ability to cache all third-party custom URLs. This enhancement allows NitroPack to effectively cache and optimize not only the website's own content but also custom URLs from third-party integrations, providing a more comprehensive caching solutio

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