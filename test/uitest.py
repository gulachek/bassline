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

    def test_admin_can_see_users_groups_page_on_admin_screen(self):
        site.logInAsUser('admin')
        page = site.gotoAdminPage()
        self.assertTrue(page.hasUsersSection())
        self.assertTrue(page.hasGroupsSection())

    def test_designer_cannot_see_users_groups_page_on_admin_screen(self):
        site.logInAsUser('designer')
        page = site.gotoAdminPage()
        self.assertFalse(page.hasUsersSection())
        self.assertFalse(page.hasGroupsSection())

class TestGroups(unittest.TestCase):
    def setUp(self):
        site.logInAsUser('admin')

    def test_created_group_is_visible_on_select_page(self):
        select = site.gotoGroupSelectPage()
        self.assertTrue(select.hasGroupname('designers'))

    def test_not_created_group_is_not_visible_on_select_page(self):
        select = site.gotoGroupSelectPage()
        self.assertFalse(select.hasGroupname('foo'))

    def test_designer_cannot_edit_group(self):
        site.logInAsUser('designer')
        page = site.gotoGroupSelectPage()
        self.assertIsNone(page)

class TestUsers(unittest.TestCase):
    def setUp(self):
        site.logInAsUser('admin')

    def test_created_user_is_visible_on_select_page(self):
        select = site.gotoUserSelectPage()
        self.assertTrue(select.hasUsername('designer'))

    def test_not_created_user_is_not_visible_on_select_page(self):
        select = site.gotoUserSelectPage()
        self.assertFalse(select.hasUsername('foo'))

    def test_designer_cannot_edit_user(self):
        site.logInAsUser('designer')
        page = site.gotoUserSelectPage()
        self.assertIsNone(page)

if __name__ == '__main__':
    unittest.main()
