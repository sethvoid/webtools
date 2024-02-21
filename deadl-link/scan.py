import requests
from bs4 import BeautifulSoup
import random
import hashlib
from time import sleep
import datetime
# ANSI escape codes for colors
red = "\033[0;31m"
green = "\033[0;32m"
reset = "\033[0m"  # Reset to default color

base_address = input(green + 'Please enter address (include protocol): ' + reset)
url = f'https://{base_address}/sitemap.xml'
response = requests.get(url)
soup = BeautifulSoup(response.text, 'xml')

amount = len(soup.find_all('loc'))
print(green + f'Identified {amount} urls in site map' + reset)

print('Running quick diagnostic to establish what bad looks like...')
bad_array = []
for _ in range(4):
    rand_num = random.randint(1, 1000)
    url = f'https://{base_address}/{hashlib.md5(str(rand_num).encode()).hexdigest()}'
    response = requests.get(url)
    bad_array.append({'code': response.status_code, 'response_size': len(response.content)})
    sleep(1)

for test_no, bad_result in enumerate(bad_array):
    print(f"Test{test_no}: {bad_result['code']}[{bad_result['response_size']}]")

errors = {}
additional = {}
urls = set([loc.text for loc in soup.find_all('loc')])

for url in urls:
    print(f'Checking {url}')
    response = requests.get(url)
    print(f'{url} ({len(response.content)})')

    if response.status_code != 200:
        errors[url] = response.status_code
        print(red + "bad link " + url + reset)
    else:
        print('Scanning for additional links')
        soup = BeautifulSoup(response.text, 'html.parser')
        potential_links = [link.get('href') for link in soup.find_all('a') if link.get('href')]
        for link in potential_links:
            if base_address in link and link not in urls:
                additional[url] = link

for source, link in additional.items():
    print(f'Checking additional link from {source}: {link}')
    response = requests.get(link)
    if response.status_code != 200:
        errors[link] = f'{response.status_code} (source url: {source})'

timestamp = datetime.datetime.now().strftime("%d-%m-%Y")
file_string = f'Dead links report: {base_address}\n'
file_string += '========================================================================\n'

if errors:
    print('Found Errors')
    print('============================================================================================')
    for url, code in errors.items():
        print(f'{url} returned {code}')
        file_string += f'{url} | return status {code}\n'

with open(f'{base_address}-{timestamp}.txt', 'w') as file:
    file.write(file_string)

print('Script finished.')
