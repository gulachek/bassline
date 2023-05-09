from selenium.webdriver.support.wait import WebDriverWait

def find_elem(driver, mode, selector, timeout=10):
    WebDriverWait(driver, timeout).until(lambda d: d.find_element(mode, selector))
    return driver.find_element(mode, selector)
