from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select

from .login import LoginPage
from .users import UserSelectPage
from .groups import GroupSelectPage
from .admin import AdminPage

from pathlib import Path
import subprocess
import os
import sys
from urllib.parse import urlparse

class Site:
    _instance = None

    def __init__(self, uri, driver, webServer):
        self.uri = uri
        self.driver = driver
        self.webServer = webServer

    def setup(self):
        self.logInAsUser('admin')
        self.createGroup('designers')
        self.createUser('designer', 'designers')
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

        if 'TEST_BASE_URI' not in os.environ:
            print('Must specify TEST_BASE_URI environment variable')
            sys.exit(1)

        uri = os.environ['TEST_BASE_URI']
        testDir = Path(__file__).parent.parent
        testData = testDir / 'data' / 'uitest'

        [os.remove(p) for p in testData.iterdir()]
        testEnv = dict(os.environ)
        testEnv['DATA_DIR'] = str(testData.absolute())

        rootDir = testDir.parent

        # Init database
        subprocess.run([
            'php', 
            f"{rootDir}/entry/init.php",
            f"{rootDir}/test/config.php"
            ], env=testEnv) 

        # Spawn web server
        netloc = urlparse(uri).netloc
        webServer = subprocess.Popen(
            args=['php', '-S', netloc, f"{rootDir}/test/server.php"],
            stderr=subprocess.PIPE,
            env=testEnv
            )
        
        driver = webdriver.Chrome()

        cls._instance = Site(uri, driver, webServer)
        cls._instance.setup()
        return cls._instance

    def currentUri(self):
        return self.driver.current_url

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

    def _navigate(self, path):
        if not self.currentUri().endswith(path):
            self.driver.get(f"{self.uri}{path}")

    def gotoHelloPage(self):
        self._navigate('/hello/')

    def gotoUserSelectPage(self):
        self._navigate('/site/admin/users/')
        return UserSelectPage.fromDriver(self.driver)

    def gotoGroupSelectPage(self):
        self._navigate('/site/admin/groups/')
        return GroupSelectPage.fromDriver(self.driver)

    def currentUsername(self):
        unames = self.driver.find_elements(By.CLASS_NAME, 'username')
        return unames[0].text if len(unames) > 0 else None

    def logOut(self):
        self._navigate('/logout/')

    def logInAsUser(self, username):
        page = self.gotoLoginPage()
        page.logInAsUser(username)

    def createUser(self, username, groupname):
        page = self.gotoUserSelectPage()
        return page.createUser(username, groupname)

    def editUser(self, username):
        page = self.gotoUserSelectPage()
        return page.selectUser(username)

    def createGroup(self, groupname):
        page = self.gotoGroupSelectPage()
        return page.createGroup(groupname)
