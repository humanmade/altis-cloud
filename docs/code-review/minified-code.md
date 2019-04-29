# Minified / Obfuscated Code

All PHP and JavaScript code needs to be provided in unminified format in a way the reviewer is able to read. In situations where minified code is committed to the git repository for code review, the unminified source should also be provided with a description of how to reproduce the minified version.

In situations where JavaScript code may need to be obfuscated, it's recommended to perform the obfuscation in the [build script](../build-scripts.md) on the server to have full source code transparency.
