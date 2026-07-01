import time
import sys
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

def run_test():
    options = webdriver.ChromeOptions()
    # options.add_argument('--headless') # Optionally run headless
    options.add_argument('--window-size=1280,720')

    print("Starting Selenium WebDriver...")
    try:
        driver = webdriver.Chrome(options=options)
    except Exception as e:
        print(f"Failed to start Chrome: {e}")
        sys.exit(1)

    try:
        print("Navigating to http://localhost:8000/admin ...")
        driver.get('http://localhost:8000/admin')
        
        # Filament usually redirects to /admin/login or similar, let's wait for a bit
        time.sleep(2)
        print(f"Current URL: {driver.current_url}")

        # Check if we are on the login page by looking for common filament login elements
        # like input name="email" or name="nip"
        
        # Let's try to find an input field. Since it might be email or NIP.
        print("Looking for login fields...")
        try:
            # Wait for input fields to be present
            login_input = WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, 'input[id="data.nip"], input[name="data.nip"], input[wire\\:model="data.nip"]'))
            )
        except:
            print("Could not find login input.")
            driver.quit()
            sys.exit(1)
            
        password_input = driver.find_element(By.CSS_SELECTOR, 'input[type="password"]')
        submit_button = driver.find_element(By.CSS_SELECTOR, 'button[type="submit"]')

        print("Entering credentials...")
        login_input.send_keys('199001012020121001')
        password_input.send_keys('199001012020121001')

        print("Submitting login form...")
        submit_button.click()

        # Wait for redirect to dashboard
        print("Waiting for dashboard redirect...")
        WebDriverWait(driver, 10).until(
            EC.url_changes(driver.current_url)
        )
        
        time.sleep(2)
        print(f"Post-login URL: {driver.current_url}")
        
        if "login" not in driver.current_url:
            print("Login successful! Navigated to dashboard.")
        else:
            print("Login failed or still on login page.")
            
    except Exception as e:
        print(f"An error occurred during testing: {e}")
    finally:
        print("Closing browser...")
        driver.quit()

if __name__ == "__main__":
    run_test()
