from selenium.webdriver.support.wait import WebDriverWait

def wait_save(driver, timeout=10):
    WebDriverWait(driver, timeout).until(edit_page_is_saved)

def edit_page_is_saved(driver):
    return driver.execute_script('return !("isBusy" in (document.querySelector(".autosave")?.dataset || {}))')
