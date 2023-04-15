from selenium.webdriver.common.by import By
from selenium.webdriver.support.wait import WebDriverWait

def edit_page_is_saved(driver):
    return driver.execute_script('let autosave = document.querySelector(".autosave"); return !!autosave && !("isBusy" in autosave.dataset);')

class AuthConfigEditPage:
    @classmethod
    def fromDriver(cls, driver):
        WebDriverWait(driver, timeout=10).until(edit_page_is_saved)
        h1s = driver.find_elements(By.TAG_NAME, 'h1')
        mainHeading = next((h for h in h1s if h.text == 'Authentication Configuration'), None)
        return None if mainHeading is None else AuthConfigEditPage(driver)

    def __init__(self, driver):
        self.driver = driver

    def _sectionElem(self, key):
        return self.driver.find_element(By.CSS_SELECTOR, f"section[data-plugin-key=\"{key}\"]")

    def _noauthCbox(self):
        return self._sectionElem('noauth').find_element(By.CSS_SELECTOR, 'input[type="checkbox"]')

    def _siwgCbox(self):
        return self._sectionElem('siwg').find_element(By.CSS_SELECTOR, 'input[type="checkbox"]')

    def _noauthCboxIsChecked(self):
        return self.driver.execute_script(f"return !!document.querySelector('section[data-plugin-key=\"noauth\"] input[type=\"checkbox\"]').checked")

    def _siwgCboxIsChecked(self):
        return self.driver.execute_script(f"return !!document.querySelector('section[data-plugin-key=\"siwg\"] input[type=\"checkbox\"]').checked")

    def noauthIsEnabled(self):
        return self._noauthCboxIsChecked()

    def siwgIsEnabled(self):
        return self._siwgCboxIsChecked()

    def toggleNoauth(self):
        self._noauthCbox().click()

    def enableNoauth(self, enabled):
        if self.noauthIsEnabled() != enabled:
            self.toggleNoauth()

    def toggleSignInWithGoogle(self):
        self._siwgCbox().click()

    def enableSignInWithGoogle(self, enabled):
        if self.siwgIsEnabled() != enabled:
            self.toggleSignInWithGoogle()

    def _siwgId(self):
        section = self._sectionElem('siwg')
        return section.find_element(By.CSS_SELECTOR, 'input[type="text"]')

    def setGoogleClientId(self, clientid):
        elem = self._siwgId()
        elem.clear()
        elem.send_keys(clientid)

    def waitSave(self):
        WebDriverWait(self.driver, timeout=10).until(edit_page_is_saved)
