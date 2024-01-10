
# calculate the yearly energy yield for a given hardware configuration at a handful of sites listed below

import calendar
import pvlib
import matplotlib.pyplot as plt
import pandas as pd
from pvlib.pvsystem import PVSystem
from pvlib.location import Location
from pvlib.modelchain import ModelChain
from pvlib.temperature import TEMPERATURE_MODEL_PARAMETERS

pd.set_option("display.max_rows", None)
pd.set_option("display.max_columns", None)

# inputs from map & configurator
    # location of system (lat/lng)
    # solar panel model name (or custom panel parameters)
    # inverter model name (or custom inverter parameters)
    # tilt of arrays (roof pitch)
    # azimuth of arrays (roof heading)
    # number of arrays
    # number of panels per array

# creating a PV system using modules and inverters from the library
sandia_modules = pvlib.pvsystem.retrieve_sam('SandiaMod')
# cec_modules = pvlib.pvsystem.retrieve_sam('CECMod')
inverters = pvlib.pvsystem.retrieve_sam('cecinverter')
# adr_inverters = pvlib.pvsystem.retrieve_sam('ADRInverter')
module_parameters = sandia_modules['Canadian_Solar_CS5P_220M___2009_']
inverter_parameters = inverters['ABB__MICRO_0_25_I_OUTD_US_208__208V_']

# creating a PV system from custom module/inverter values
# module_parameters = {'pdc0': 5000, 'gamma_pdc': -0.004}
# inverter_parameters = {'pdc0': 5000, 'eta_inv_nom': 0.96}
temperature_model_parameters = TEMPERATURE_MODEL_PARAMETERS['sapm']['open_rack_glass_glass']
# temperature_model_parameters = pvlib.temperature.TEMPERATURE_MODEL_PARAMETERS['sapm']['open_rack_glass_glass']

# roof_pitch = Tilt angle of the module surface. Up=0, horizon=90.
roof_pitch = 20
# roof_direction = Azimuth angle of the module surface. North=0, East=90, South=180, West=270.
roof_direction = 180
modules_per_string = 4
strings_per_inverter = 5

system = PVSystem(
    surface_tilt=roof_pitch,
    surface_azimuth=roof_direction,
    module_parameters=module_parameters,
    inverter_parameters=inverter_parameters,
    temperature_model_parameters=temperature_model_parameters,
    modules_per_string=modules_per_string,
    strings_per_inverter=strings_per_inverter
)

# lat, lng, name, altitude, timezone
coordinates = [
    (41.710403, -74.395075, 'Ellenville', 340, 'Etc/GMT+5')
]

energies = []

print('\nCalculating estimated monthly energy output for PV system:')
print('\nModule: '+module_parameters.name)
print('\nInverter: '+inverter_parameters.name)
print('\nArray surface tilt (roof pitch): '+str(roof_pitch))
print('\nArray surface azimuth (roof direction): '+str(roof_direction))
print('\nNumber of solar modules: '+(str(modules_per_string*strings_per_inverter))+" ("+str(modules_per_string)+" x "+str(strings_per_inverter)+")")

for latitude, longitude, name, altitude, timezone in coordinates:
    location = Location(latitude, longitude, name=name, altitude=altitude, tz=timezone)
    print('\nLocation: '+name+" ("+str(latitude)+", "+str(longitude)+")")

    # you can also create a weather DataFrame statically
    # weather = pd.DataFrame([[1050, 1000, 100, 30, 5]],
    #                    columns=['ghi', 'dni', 'dhi', 'temp_air', 'wind_speed'],
    #                    index=[pd.Timestamp('20170401 1200', tz='US/Arizona')])

    # get TMY for each location
    # Typical Meteorological Year - irradiation, temperature and wind speed
    tmy = pvlib.iotools.get_pvgis_tmy(latitude, longitude)[0]
    weathers = [[] for y in range(13)]

    # separate TMY by month
    for index, row in tmy.iterrows():
        index = pd.to_datetime(index).month
        row.index.name = "utc_time"
        weathers[index].append(row)

    # create and run the model for each month of weather data
    mc = ModelChain(system, location)
    for i in range(1,13):
        mc.run_model(pd.DataFrame(weathers[i]))
        energies.append(mc.results.ac.sum())

    print("\nMonthly output (kwh):")
    i = 1
    for index in energies:
        print(calendar.month_name[i][0:3]+": "+str(index))
        i += 1


# In order to retrieve meteorological data for the simulation, we can make use of the IO Tools module. 
# In this example we will be using PVGIS to retrieve a Typical Meteorological Year (TMY) which includes irradiation, temperature and wind speed.

# get the yearly energy output for each location using the configured PV system

# energies = {}

# for location, weather in zip(coordinates, tmys):

#     latitude, longitude, name, altitude, timezone = location

#     system['surface_tilt'] = latitude

#     solpos = pvlib.solarposition.get_solarposition(
#         time=weather.index,
#         latitude=latitude,
#         longitude=longitude,
#         altitude=altitude,
#         temperature=weather["temp_air"],
#         pressure=pvlib.atmosphere.alt2pres(altitude),
#     )

#     dni_extra = pvlib.irradiance.get_extra_radiation(weather.index)

#     airmass = pvlib.atmosphere.get_relative_airmass(solpos['apparent_zenith'])

#     pressure = pvlib.atmosphere.alt2pres(altitude)

#     am_abs = pvlib.atmosphere.get_absolute_airmass(airmass, pressure)

#     aoi = pvlib.irradiance.aoi(
#         system['surface_tilt'],
#         system['surface_azimuth'],
#         solpos["apparent_zenith"],
#         solpos["azimuth"],
#     )

#     total_irradiance = pvlib.irradiance.get_total_irradiance(
#         system['surface_tilt'],
#         system['surface_azimuth'],
#         solpos['apparent_zenith'],
#         solpos['azimuth'],
#         weather['dni'],
#         weather['ghi'],
#         weather['dhi'],
#         dni_extra=dni_extra,
#         model='haydavies',
#     )

#     cell_temperature = pvlib.temperature.sapm_cell(
#         total_irradiance['poa_global'],
#         weather["temp_air"],
#         weather["wind_speed"],
#         **temperature_model_parameters,
#     )

#     effective_irradiance = pvlib.pvsystem.sapm_effective_irradiance(
#         total_irradiance['poa_direct'],
#         total_irradiance['poa_diffuse'],
#         am_abs,
#         aoi,
#         module,
#     )

#     dc = pvlib.pvsystem.sapm(effective_irradiance, cell_temperature, module)
#     ac = pvlib.inverter.sandia(dc['v_mp'], dc['p_mp'], inverter)
#     annual_energy = ac.sum()
#     energies[name] = annual_energy

# energies = pd.Series(energies)
# print(energies)
