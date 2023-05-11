from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select

from .login import LoginPage
from .users import UserSelectPage
from .groups import GroupSelectPage
from .admin import AdminPage
from .auth import AuthConfigEditPage
from .color_palette import ColorPaletteSelectPage
from .theme import ThemeSelectPage

from pathlib import Path
import subprocess
import os
import sys
from urllib.parse import urlparse, urlencode

class Site:
    _instance = None

    def __init__(self, uri, driver, webServer, rootDir):
        self.uri = uri
        self.driver = driver
        self.webServer = webServer
        self.rootDir = rootDir

    def setup(self):
        self.logInAsUser('admin')
        self.enableAuth()
        # Designers
        groupPage = self.createGroup('designers')
        groupPage.toggleSecurity('shell', 'edit_themes')
        groupPage.waitSave()
        self.createUser('designer', 'designers')
        # Plebs
        self.createGroup('plebs')
        self.createUser('pleb', 'plebs')
        self.logOut()

    def close():
        self.driver.close()
        self.webServer.kill()

    # Unit test framework doesn't make test fixture initialization
    # Seem obvious, so 😭 go with singleton
    @classmethod
    def instance(cls):
        if cls._instance is not None:
            return csl._instance

        uri = os.environ.get('TEST_BASE_URI', 'http://localhost:9998' )
        testDir = Path(__file__).parent.parent
        testData = testDir / 'data' / 'uitest'

        [os.remove(p) for p in testData.iterdir()]
        testEnv = dict(os.environ)
        testEnv['DATA_DIR'] = str(testData.absolute())
        testEnv['SITE_CONFIG_PATH'] = str(testDir / 'config.php')

        rootDir = testDir.parent

        # Init database
        subprocess.run([
            'php', 
            f"{rootDir}/bin/init.php",
            f"{rootDir}/test/config.php"
            ], env=testEnv) 

        # Spawn web server
        netloc = urlparse(uri).netloc
        webServer = subprocess.Popen(
            args=['php', '-S', netloc, f"{rootDir}/bin/serve.php"],
            stderr=subprocess.PIPE,
            env=testEnv
            )
        
        driver = webdriver.Chrome()

        cls._instance = Site(uri, driver, webServer, rootDir)
        cls._instance.setup()
        return cls._instance

    def currentUri(self):
        return self.driver.current_url

    def currentPath(self):
        return urlparse(self.currentUri()).path

    def refresh(self):
        self.driver.get(self.driver.current_url)

    def gotoLoginPage(self):
        self._navigate('/login/')
        return LoginPage(self.driver)

    def gotoAdminPage(self):
        self._navigate('/site/admin/')
        return AdminPage(self.driver)

    def clickLoginLink(self):
        login = self.driver.find_elements(By.CLASS_NAME, 'login')
        assert len(login) > 0
        login[0].click()
        return LoginPage(self.driver)

    def _navigate(self, path):
        if self.currentPath() != path:
            self.driver.get(f"{self.uri}{path}")

    def gotoLandingPage(self):
        self.driver.get(f"{self.uri}/")
        return 'find me selenium' in self.driver.page_source

    def gotoHelloPage(self):
        self._navigate('/hello/')

    def gotoUserSelectPage(self):
        self._navigate('/site/admin/users/')
        return UserSelectPage.fromDriver(self.driver)

    def gotoGroupSelectPage(self):
        self._navigate('/site/admin/groups/')
        return GroupSelectPage.fromDriver(self.driver)

    def gotoColorPaletteSelectPage(self):
        self._navigate('/site/admin/color_palette/')
        return ColorPaletteSelectPage.fromDriver(self.driver)

    def gotoThemeSelectPage(self):
        self._navigate('/site/admin/theme/')
        return ThemeSelectPage.fromDriver(self.driver)

    def gotoAuthConfigEditPage(self):
        self._navigate('/site/admin/auth_config/')
        return AuthConfigEditPage.fromDriver(self.driver)

    def currentUsername(self):
        unames = self.driver.find_elements(By.CLASS_NAME, 'username')
        return unames[0].text if len(unames) > 0 else None

    def logOut(self):
        self._navigate('/logout/')

    def logInAsUser(self, username):
        testEnv = dict(os.environ)
        testDir = Path(__file__).parent.parent
        testData = testDir / 'data' / 'uitest'
        testEnv['DATA_DIR'] = str(testData.absolute())
        testEnv['SITE_CONFIG_PATH'] = str(testDir / 'config.php')
        proc = subprocess.run(
            args=['php',
                  f"{self.rootDir}/bin/issue_nonce.php",
                  f"{self.rootDir}/test/config.php",
                  username
                  ],
            capture_output=True,
            text=True,
            env=testEnv
            )
        nonce = proc.stdout.strip()
        query = urlencode({ 'auth': 'nonce', 'nonce': nonce })
        self._navigate(f"/login/attempt?{query}")

    def enableAuth(self):
        page = self.gotoAuthConfigEditPage()
        page.enableNoauth(True)
        page.setGoogleClientId('dummy')
        page.enableSignInWithGoogle(True)
        page.waitSave()

    def createUser(self, username, groupname):
        select = self.gotoUserSelectPage()
        edit = select.createUser()
        edit.setUsername(username)

        if not edit.isGroupMember(groupname):
            edit.toggleGroup(groupname)
            oldPrimary = edit.primaryGroup()
            edit.setPrimaryGroup(groupname)
            edit.toggleGroup(oldPrimary)

        edit.waitSave()
        return edit

    def editUser(self, username):
        page = self.gotoUserSelectPage()
        return page.selectUser(username)

    def createGroup(self, groupname):
        page = self.gotoGroupSelectPage()
        return page.createGroup(groupname)

    def createPalette(self, name):
        page = self.gotoColorPaletteSelectPage()
        return page.createPalette(name)

    def createTheme(self):
        page = self.gotoThemeSelectPage()
        return page.createTheme()
