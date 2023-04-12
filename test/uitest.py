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
        page = site.clickLoginLink()
        page.logInAsUser('admin')
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

class TestConfig(unittest.TestCase):
    def test_renders_landing_page(self):
        self.assertTrue(site.gotoLandingPage())

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

    def test_group_is_editable(self):
        edit = site.createGroup('edit_me')

        # Defaults
        self.assertEqual(edit.groupname(), 'edit_me')
        self.assertFalse(edit.hasSecurity('shell', 'edit_groups'))
        self.assertFalse(edit.hasSecurity('shell', 'edit_users'))
        self.assertFalse(edit.hasSecurity('hello', 'edit_greeting'))

        # Now edit
        edit.setGroupname('edit_me_test')
        edit.toggleSecurity('shell', 'edit_groups')
        edit.toggleSecurity('hello', 'edit_greeting')

        edit.waitSave()
        site.refresh()

        self.assertEqual(edit.groupname(), 'edit_me_test')
        self.assertTrue(edit.hasSecurity('shell', 'edit_groups'))
        self.assertFalse(edit.hasSecurity('shell', 'edit_users'))
        self.assertTrue(edit.hasSecurity('hello', 'edit_greeting'))

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

    def test_user_is_editable(self):
        edit = site.createUser('edit_me', 'designers')

        # Defaults
        self.assertEqual(edit.username(), 'edit_me')
        self.assertEqual(edit.primaryGroup(), 'designers')
        self.assertFalse(edit.isGroupMember('staff'))
        self.assertTrue(edit.isGroupMember('designers'))
        self.assertSetEqual(edit.emails(), set())

        # Now edit
        edit.setUsername('edit_me_test')
        edit.toggleGroup('staff')
        edit.setPrimaryGroup('staff')
        emails = {'test@example.com', 'test@example.org'}
        [edit.addEmail(e) for e in emails]

        edit.waitSave()
        site.refresh()

        self.assertEqual(edit.username(), 'edit_me_test')
        self.assertEqual(edit.primaryGroup(), 'staff')
        self.assertTrue(edit.isGroupMember('staff'))
        self.assertTrue(edit.isGroupMember('designers'))
        self.assertSetEqual(edit.emails(), emails)

class TestColorPalette(unittest.TestCase):
    def setUp(self):
        site.logInAsUser('designer')

    def test_created_palette_is_visible_on_select_page(self):
        site.createPalette('Can Create')
        select = site.gotoColorPaletteSelectPage()
        self.assertTrue(select.hasPalette('Can Create'))

    def test_pleb_cannot_edit_palette(self):
        site.logInAsUser('pleb')
        page = site.gotoColorPaletteSelectPage()
        self.assertIsNone(page)

    def test_palette_is_editable(self):
        edit = site.createPalette('Edit me')

        # Defaults
        self.assertEqual(edit.paletteName(), 'Edit me')
        self.assertDictEqual(edit.colors(), {
            'New Color': '#000000'
            })

        # Now edit
        edit.setPaletteName('Edited Name')
        edit.setColor('New Color', name='Red', color='#ff0000')
        edit.addColor(name='Green', color='#00ff00')
        edit.addColor(name='Yellow', color='#ffff00')
        edit.addColor(name='Blue', color='#0000ff')
        edit.deleteColor('Yellow')

        edit.waitSave()
        site.refresh()

        self.assertEqual(edit.paletteName(), 'Edited Name')
        self.assertDictEqual(edit.colors(), {
            'Red': '#ff0000',
            'Green': '#00ff00',
            'Blue': '#0000ff'
            })

class TestTheme(unittest.TestCase):
    def setUp(self):
        site.logInAsUser('designer')

    def test_created_theme_is_visible_on_select_page(self):
        edit = site.createTheme()
        edit.setThemeName('Can Create')
        edit.waitSave()
        select = site.gotoThemeSelectPage()
        self.assertTrue(select.hasTheme('Can Create'))

    def test_pleb_cannot_edit_theme(self):
        site.logInAsUser('pleb')
        page = site.gotoThemeSelectPage()
        self.assertIsNone(page)

    def test_theme_is_editable(self):
        # Set up palette
        palette = site.createPalette('For theme')
        palette.setColor('New Color', name='Red', color='#ff0000')
        palette.addColor(name='Green', color='#00ff00')
        palette.addColor(name='Blue', color='#0000ff')
        palette.waitSave()

        # Create theme
        edit = site.createTheme()
        edit.changePalette('For theme')

        # Defaults
        self.assertEqual(edit.themeName(), 'New Theme')
        self.assertEqual(edit.activeStatus(), 'inactive')

        # Now edit
        edit.setThemeName('Edited Name')
        edit.setActiveStatus('dark')
        edit.setThemeColor('New Color', name='First',
                        paletteColorName='Red', lightness=0.1
                           )

        edit.addThemeColor(name='First Fg',
                        paletteColorName='Red', lightness=0.8
                           )

        edit.addThemeColor(name='Delete me',
                      paletteColorName='Blue', lightness=0.2
                      )
        edit.addThemeColor(name='Christmas',
                      paletteColorName='Green', lightness=0.3,
                      )

        # Can only map real colors
        edit.waitSave()

        edit.mapColor('shell', 'page-bg', 'First')
        edit.mapColor('shell', 'page-fg', 'First Fg')
        edit.mapColor('shell', 'clickable-bg', 'Christmas')
        edit.mapColor('hello', 'greeting-bg', 'Christmas')
        edit.mapColor('hello', 'title-bg', 'Delete me') # don't care about result, just that we handle it

        edit.deleteThemeColor('Delete me')

        edit.waitSave()
        site.refresh()

        self.assertEqual(edit.themeName(), 'Edited Name')
        self.assertEqual(edit.activeStatus(), 'dark')
        self.assertDictEqual(edit.themeColors(), {
            'First': {
                'color': 'Red',
                'lightness': 0.1,
                },
            'First Fg': {
                'color': 'Red',
                'lightness': 0.8,
                },
            'Christmas': {
                'color': 'Green',
                'lightness': 0.3,
                }
            })

        mappings = edit.mappings()
        self.assertEqual('First', mappings['shell']['page-bg'])
        self.assertEqual('First Fg', mappings['shell']['page-fg'])
        self.assertEqual('Christmas', mappings['shell']['clickable-bg'])
        self.assertEqual('Christmas', mappings['hello']['greeting-bg'])

if __name__ == '__main__':
    unittest.main()
