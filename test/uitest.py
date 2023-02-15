#!/usr/bin/env python3

import unittest

from uilib.site import Site

import os
import sys
from urllib.parse import urlparse

site = Site.instance()

class TestLogin(unittest.TestCase):
    def setUp(self):
        site.logOut()

    def assertUri(self, uri):
        current = urlparse(site.currentUri())
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
        site.logInAsUser('admin')
        self.assertEqual(site.currentUsername(), 'admin')

    def test_logout_eliminates_user(self):
        site.logInAsUser('admin')
        site.logOut()
        self.assertIsNone(site.currentUsername())

    def test_login_redirects_to_original_page(self):
        site.gotoHelloPage()
        uri = site.currentUri()
        site.clickLoginLink()
        site.logInAsUser('admin')
        self.assertUri(uri)

    def test_logout_redirects_to_base_page(self):
        baseUri = site.currentUri()
        site.logInAsUser('admin')
        site.gotoHelloPage()
        site.logOut()
        self.assertUri(baseUri)

class TestUsers(unittest.TestCase):
    def setUp(self):
        site.logInAsUser('admin')

    def test_admin_visible_select_page(self):
        select = site.gotoUserSelectPage()
        self.assertTrue(select.hasUsername('admin'))

    def test_create_user_makes_user_on_select_screen(self):
        select = site.gotoUserSelectPage()
        self.assertFalse(select.hasUsername('foo'))
        select.createUser('foo')
        select = site.gotoUserSelectPage()
        self.assertTrue(select.hasUsername('foo'))

if __name__ == '__main__':
    unittest.main()
