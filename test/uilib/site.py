from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select

from .login import LoginPage
from .users import UserSelectPage

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
        self.createUser('test')
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

    def gotoLoginPage(self):
        self.driver.get(f"{self.uri}/login/")
        return LoginPage(self.driver)

    def clickLoginLink(self):
        login = self.driver.find_elements(By.CLASS_NAME, 'login')
        assert len(login) > 0
        login[0].click()

    def gotoHelloPage(self):
        self.driver.get(f"{self.uri}/hello/")

    def gotoUserSelectPage(self):
        self.driver.get(f"{self.uri}/site/admin/users/")
        return UserSelectPage(self.driver)

    def currentUsername(self):
        unames = self.driver.find_elements(By.CLASS_NAME, 'username')
        return unames[0].text if len(unames) > 0 else None

    def logOut(self):
        self.driver.get(f"{self.uri}/logout/")

    def logInAsUser(self, username):
        if not self.driver.current_url.endswith('/login/'):
            self.gotoLoginPage()

        page = LoginPage(self.driver)
        page.logInAsUser(username)

    def createUser(self, username):
        if not self.currentUri().endswith('/site/admin/users/'):
            self.gotoUserSelectPage()

        page = UserSelectPage(self.driver)
        page.createUser(username)
