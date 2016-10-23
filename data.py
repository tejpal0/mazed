from random import randint
male_names = open('male_names.txt', 'rU').readlines()
female_names = open('female_names.txt', 'rU').readlines()
ages = open('age.txt', 'rU').readlines()
gender = ['male' for _ in xrange(len(male_names))] + ['female' for _ in xrange(len(female_names))]
names = male_names + female_names
geodata = open('locations.txt', 'rU').readlines()
for i in xrange(1000):
    identification = randint(1000, 9999)
    userid = names[i].strip().split()[0] + ages[i].strip() + str(identification)
    coords = geodata[i].strip().split(',')
    lat, lng = coords[1], coords[3]
    name = names[i].strip().split()
    name = name[0] + " " + name[1]
    print '"'+userid.strip() + '","' + name + '","' + ages[i].strip() + '","' + gender[i].strip() + '","' + lat + '","' + lng + '"'
