#!/usr/bin/env python3

import unittest

from uilib.site import Site

from selenium import webdriver
from selenium.webdriver.common.by import By

import os
import sys
import subprocess
from pathlib import Path
from urllib.parse import urlparse

if 'TEST_BASE_URI' not in os.environ:
    print('Must specify TEST_BASE_URI environment variable')
    sys.exit(1)

uri = os.environ['TEST_BASE_URI']
dirname = os.path.dirname(__file__)
rootDir = os.path.dirname(dirname) # we know the source structure 😂
testData = Path(dirname) / 'data' / 'uitest'

class TestLogin(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        [os.remove(p) for p in testData.iterdir()]
        testEnv = dict(os.environ)
        testEnv['DATA_DIR'] = str(testData.absolute())
        #print(testEnv)

        # Init database
        subprocess.run([
            'php', 
            f"{rootDir}/entry/init.php",
            f"{rootDir}/test/config.php"
            ], env=testEnv) 

        # Spawn web server
        netloc = urlparse(uri).netloc
        cls._webServer = subprocess.Popen(
            args=['php', '-S', netloc, f"{rootDir}/test/server.php"],
            stderr=subprocess.PIPE,
            env=testEnv
            )
        
        cls._driver = webdriver.Chrome()

    @classmethod
    def tearDownClass(cls):
        cls._driver.close()
        cls._webServer.kill()

    @classmethod
    def currentUri(cls):
        return cls._driver.current_url

    def setUp(self):
        self.site = Site(uri, TestLogin._driver)
        self.site.logOut()

    def assertUri(self, uri):
        current = urlparse(TestLogin.currentUri())
        target = urlparse(uri)
        self.assertEqual(current.netloc, target.netloc)
        currentPath = current.path
        targetPath = target.path
        if not currentPath.endswith('/'):
            currentPath += '/'
        if not targetPath.endswith('/'):
            targetPath += '/'
        self.assertEqual(currentPath, targetPath)

    def test_username_matches_user(self):
        self.site.logInAsUser('admin')
        self.assertEqual(self.site.currentUsername(), 'admin')

    def test_logout_eliminates_user(self):
        self.site.logInAsUser('admin')
        self.site.logOut()
        self.assertIsNone(self.site.currentUsername())

    def test_login_redirects_to_original_page(self):
        self.site.gotoHelloPage()
        uri = TestLogin.currentUri()
        self.site.clickLoginLink()
        self.site.logInAsUser('admin')
        self.assertUri(uri)

    def test_logout_redirects_to_base_page(self):
        baseUri = TestLogin.currentUri()
        self.site.logInAsUser('admin')
        self.site.gotoHelloPage()
        self.site.logOut()
        self.assertUri(uri)

if __name__ == '__main__':
    unittest.main()
